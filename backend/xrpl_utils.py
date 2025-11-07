import xrpl
import json
import hashlib

async def send_quiz_proof(wallet_seed, data_dict):
    # prepara hash del risultato
    root = hashlib.sha256(json.dumps(data_dict, sort_keys=True).encode()).hexdigest()
    client = xrpl.Client("wss://s.altnet.rippletest.net:51233")
    await client.connect()
    wallet = xrpl.wallet.Wallet(seed=wallet_seed, sequence=0)

    tx = {
        "TransactionType": "Payment",
        "Account": wallet.classic_address,
        "Amount": "10",
        "Destination": wallet.classic_address, # account del server
        "Memos": [{"Memo": {"MemoData": root.encode().hex()}}]
    }

    prepared = await client.autofill(tx)
    signed = wallet.sign(prepared)
    result = await client.submit_and_wait(signed.tx_blob)
    await client.close()

    return result.result["tx_json"]["hash"]
