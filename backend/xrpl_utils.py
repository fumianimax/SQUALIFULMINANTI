# backend/xrpl_utils.py
import xrpl
import json
import hashlib
import os
from dotenv import load_dotenv  # <-- AGGIUNGI
import asyncio

load_dotenv()  # <-- AGGIUNGI

SERVER_XRPL_ADDRESS = os.getenv("SERVER_XRPL_ADDRESS")
if not SERVER_XRPL_ADDRESS:
    raise ValueError("SERVER_XRPL_ADDRESS mancante nel .env")

async def send_quiz_proof(wallet_seed, data_dict):
    # prepara hash del risultato
    root = hashlib.sha256(json.dumps(data_dict, sort_keys=True).encode()).hexdigest()
    
    client = xrpl.clients.JsonRpcClient("https://s.altnet.rippletest.net:51234/")
    
    wallet = xrpl.wallet.Wallet(seed=wallet_seed, sequence=0)

    tx = {
        "TransactionType": "Payment",
        "Account": wallet.classic_address,
        "Amount": "10",  # 10 drops = 0.00001 XRP
        "Destination": SERVER_XRPL_ADDRESS,
        "Memos": [{"Memo": {"MemoData": root.encode().hex()}}]
    }

    # Usa asyncio.to_thread per evitare blocchi
    prepared = await asyncio.to_thread(client.request, xrpl.models.requests.AccountInfo(
        account=wallet.classic_address, ledger_index="validated"
    ))
    sequence = prepared.result["account_data"]["Sequence"]

    tx["Sequence"] = sequence

    signed = wallet.sign(xrpl.transaction.safe_sign_transaction(tx, wallet))
    result = await asyncio.to_thread(client.submit, signed.tx_blob)
    
    return result.result["tx_json"]["hash"]