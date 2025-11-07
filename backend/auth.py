from fastapi import APIRouter, HTTPException, Depends
from passlib.context import CryptContext
from jose import jwt
from backend.database import users_col
import xrpl

router = APIRouter()
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
SECRET = "supersecretjwtkey"

def hash_password(pw): return pwd_context.hash(pw)
def verify_password(pw, hashed): return pwd_context.verify(pw, hashed)

@router.post("/register")
async def register_user(data: dict):
    username = data["username"]
    password = data["password"]

    if users_col.find_one({"username": username}):
        raise HTTPException(status_code=400, detail="User already exists")

    # crea XRPL account su testnet
    client = xrpl.Client("wss://s.altnet.rippletest.net:51233")
    await client.connect()
    wallet = await xrpl.wallet.generate_faucet_wallet(client)
    await client.close()

    users_col.insert_one({
        "username": username,
        "password": hash_password(password),
        "xrpl_address": wallet.classic_address,
        "seed": wallet.seed,  # in produzione non salvarla! (qui solo per test)
    })

    return {"msg": "User registered", "xrpl_address": wallet.classic_address}

@router.post("/login")
async def login_user(data: dict):
    user = users_col.find_one({"username": data["username"]})
    if not user or not verify_password(data["password"], user["password"]):
        raise HTTPException(status_code=401, detail="Invalid credentials")

    token = jwt.encode({"sub": user["username"]}, SECRET)
    return {"access_token": token, "xrpl_address": user["xrpl_address"]}
