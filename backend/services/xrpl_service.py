from xrpl.clients import JsonRpcClient
from xrpl.wallet import Wallet
from xrpl.models.transactions import Payment
from xrpl.transaction import safe_sign_and_autofill_transaction, send_reliable_submission

JSON_RPC_URL = "https://s.altnet.rippletest.net:51234"
client = JsonRpcClient(JSON_RPC_URL)

def send_xrp(sender_seed, receiver_address, amount):
    wallet = Wallet(seed=sender_seed, sequence=0)
    payment = Payment(
        account=wallet.address,
        amount=str(int(amount * 1_000_000)),  # XRP -> drops
        destination=receiver_address
    )
    signed_tx = safe_sign_and_autofill_transaction(payment, wallet, client)
    tx_response = send_reliable_submission(signed_tx, client)
    return tx_response.result
