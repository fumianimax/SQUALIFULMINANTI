from xrpl.wallet import generate_faucet_wallet
from xrpl.clients import JsonRpcClient
from fastapi import APIRouter, HTTPException
from passlib.context import CryptContext
from jose import jwt
from backend.database import users_col

router = APIRouter()
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
SECRET = "supersecretjwtkey"

def hash_password(pw): return pwd_context.hash(pw)
def verify_password(pw, hashed): return pwd_context.verify(pw, hashed)

@router.post("/register")
async def register_user(data: dict):
    username = data["username"]
    password = data["password"]

    if users_col and users_col.find_one({"username": username}):
        raise HTTPException(status_code=400, detail="User already exists")

    JSON_RPC_URL = "https://s.altnet.rippletest.net:51234"
    client = JsonRpcClient(JSON_RPC_URL)

    # genera un wallet sulla testnet
    try:
        wallet = generate_faucet_wallet(client, debug=True)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"XRPL wallet generation failed: {e}")

    users_col.insert_one({
        "username": username,
        "password": hash_password(password),
        "xrpl_address": wallet.classic_address,
        "seed": wallet.seed,
    })

    return {"msg": "User registered", "xrpl_address": wallet.classic_address}


@router.post("/login")
async def login_user(data: dict):
    user = users_col.find_one({"username": data["username"]})
    if not user or not verify_password(data["password"], user["password"]):
        raise HTTPException(status_code=401, detail="Invalid credentials")

    token = jwt.encode({"sub": user["username"]}, SECRET)
    return {"access_token": token, "xrpl_address": user["xrpl_address"]}
