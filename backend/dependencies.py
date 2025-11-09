# backend/dependencies.py
from fastapi import Depends, HTTPException, Header
from jose import jwt, JWTError
from backend.config import JWT_SECRET, JWT_ALGORITHM
from backend.database import users_col

async def get_current_user(authorization: str = Header(None)):
    """
    Extract user from the JWT token from the header Authorization: Bearer <token>
    """
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Token missing or not found")

    token = authorization.split(" ")[1]

    try:
        payload = jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise HTTPException(status_code=401, detail="Token not valid")
    except JWTError as e:
        raise HTTPException(status_code=401, detail=f"Token expired or not valid: {e}")

    # Check if the user exists in the DB
    user = users_col.find_one({"username": username})
    if not user:
        raise HTTPException(status_code=401, detail="User not found")

    return {"username": username}