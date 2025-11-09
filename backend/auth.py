# backend/auth.py
from xrpl.wallet import generate_faucet_wallet
from xrpl.clients import JsonRpcClient
from fastapi import APIRouter, HTTPException
from passlib.context import CryptContext
from jose import jwt
from backend.database import users_col
from backend.config import JWT_SECRET, JWT_ALGORITHM
import asyncio

router = APIRouter()
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def hash_password(pw: str) -> str:
    pw = pw[:72]
    return pwd_context.hash(pw)

def verify_password(pw: str, hashed: str) -> bool:
    pw = pw[:72]
    return pwd_context.verify(pw, hashed)

@router.post("/register")
async def register_user(data: dict):
    username = data.get("username")
    password = data.get("password")
    if not username or not password:
        raise HTTPException(400, "Missing field")
    if users_col.find_one({"username": username}):
        raise HTTPException(400, "Existing user! Please Sign-in!")

    client = JsonRpcClient("https://s.altnet.rippletest.net:51234")

    try:
        # Wallet generation
        wallet = await asyncio.to_thread(generate_faucet_wallet, client, debug=True)
        
        users_col.insert_one({
            "username": username,
            "password": hash_password(password),
            "xrpl_address": wallet.classic_address,
            "seed": wallet.seed,
            "initial_balance": 10  
        })

        return {"msg": "User registered!", "XRPL Address": wallet.classic_address}

    except Exception as e:
        raise HTTPException(500, f"Error XRPL: {e}")
    
@router.post("/login")
async def login_user(data: dict):
    username = data.get("username")
    password = data.get("password")
    user = users_col.find_one({"username": username})
    if not user or not verify_password(password, user["password"]):
        raise HTTPException(401, "Wrong credentials")
    token = jwt.encode({"sub": username}, JWT_SECRET, algorithm=JWT_ALGORITHM)
    return {"access_token": token, "xrpl_address": user["xrpl_address"]}