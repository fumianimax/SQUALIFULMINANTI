import json
import time
from xrpl.clients import JsonRpcClient
from xrpl.wallet import Wallet
from xrpl.models.transactions import NFTokenMint
from xrpl.transaction import sign_and_submit, autofill_and_sign

JSON_RPC_URL = "https://s.altnet.rippletest.net:51234"
client = JsonRpcClient(JSON_RPC_URL)

def mint_certificate(sender_seed, to_wallet, quiz_name, score):
    wallet = Wallet(seed=sender_seed, sequence=0)
    metadata = {
        "quiz": quiz_name,
        "score": score,
        "timestamp": int(time.time())
    }

    mint_tx = NFTokenMint(
        account=wallet.address,
        uri=json.dumps(metadata).encode("utf-8").hex(),
        flags=8  # transferable
    )

    signed_tx = autofill_and_sign(mint_tx, wallet, client)
    tx_response = sign_and_submit(signed_tx, client)
    return tx_response.result
