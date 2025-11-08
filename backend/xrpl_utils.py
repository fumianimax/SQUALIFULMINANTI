# backend/xrpl_utils.py
import os
import json
import hashlib
import logging
import httpx
from dotenv import load_dotenv
from xrpl.clients import JsonRpcClient
from xrpl.wallet import Wallet
from xrpl.models.transactions import Payment
from xrpl.asyncio.transaction import autofill_and_sign, submit_and_wait
from xrpl.utils import str_to_hex, xrp_to_drops

load_dotenv()

# CONFIG
SERVER_SEED = os.getenv("XRPL_SEED")
SERVER_XRPL_ADDRESS = os.getenv("SERVER_XRPL_ADDRESS")
if not SERVER_SEED or not SERVER_XRPL_ADDRESS:
    raise RuntimeError("XRPL_SEED e SERVER_XRPL_ADDRESS obbligatori nel .env")

client = JsonRpcClient("https://s.altnet.rippletest.net:51234")

# -------------------------------------------------
# FUND SERVER – VERSIONE SICURA PER MAC + PYTHON 3.14
# -------------------------------------------------
async def fund_server():
    try:
        # Controlla saldo
        info = await client.request({
            "command": "account_info",
            "account": SERVER_XRPL_ADDRESS,
            "ledger_index": "validated"
        })
        balance_drops = int(info.result["account_data"]["Balance"])
        if balance_drops >= 200_000_000:  # 200 XRP
            logging.info(f"Server già fundato: {balance_drops / 1_000_000:.2f} XRP")
            return

        logging.info("Richiedo fondi dal faucet testnet...")
        
        # Usa httpx in modo CORRETTO (no asyncio.run!)
        async with httpx.AsyncClient(timeout=30.0) as http:
            response = await http.post(
                "https://faucet.altnet.rippletest.net/accounts",
                json={"destination": SERVER_XRPL_ADDRESS}
            )
            
            if response.status_code == 200:
                data = response.json()
                amount = data.get("amount", "100")
                logging.info(f"Faucet OK: +{amount} XRP in arrivo a {SERVER_XRPL_ADDRESS}")
            else:
                logging.warning(f"Faucet ha risposto {response.status_code}: {response.text}")

    except Exception as e:
        logging.error(f"Errore funding server: {e}")
        pass

# -------------------------------------------------
# PROOF: USER → SERVER
# -------------------------------------------------
async def send_quiz_proof(wallet_seed: str, data_dict: dict) -> str:
    try:
        wallet = Wallet.from_seed(wallet_seed)
        data_str = json.dumps(data_dict, sort_keys=True, separators=(',', ':'))
        memo_hash = hashlib.sha256(data_str.encode()).hexdigest()

        tx = Payment(
            account=wallet.classic_address,
            destination=SERVER_XRPL_ADDRESS,
            amount="10",
            memos=[{
                "memo": {
                    "memo_data": str_to_hex(memo_hash),
                    "memo_type": str_to_hex("quiz_proof"),
                    "memo_format": str_to_hex("text/plain")
                }
            }]
        )

        signed_tx = await autofill_and_sign(tx, client, wallet)
        response = await submit_and_wait(signed_tx, client)
        tx_hash = response.result["hash"]
        logging.info(f"PROOF INVIATA: {tx_hash}")
        return tx_hash

    except Exception as e:
        err = str(e)[:100]
        logging.error(f"PROOF FALLITA: {err}")
        return f"proof_error: {err}"

# -------------------------------------------------
# PREMIO: SERVER → USER
# -------------------------------------------------
async def send_prize(to_address: str, amount_xrp: float) -> str:
    try:
        server_wallet = Wallet.from_seed(SERVER_SEED)

        tx = Payment(
            account=server_wallet.classic_address,
            destination=to_address,
            amount=xrp_to_drops(amount_xrp)
        )

        signed_tx = await autofill_and_sign(tx, client, server_wallet)
        response = await submit_and_wait(signed_tx, client)
        tx_hash = response.result["hash"]
        logging.info(f"PREMIO {amount_xrp} XRP → {to_address} | TX: {tx_hash}")
        return tx_hash

    except Exception as e:
        err = str(e)[:100]
        logging.error(f"PREMIO FALLITO: {err}")
        return f"tx_error: {err}"