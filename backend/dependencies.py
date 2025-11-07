# backend/dependencies.py
from fastapi import Depends, HTTPException, Header
from jose import jwt, JWTError
from backend.config import JWT_SECRET, JWT_ALGORITHM
from backend.database import users_col

async def get_current_user(authorization: str = Header(None)):
    """
    Estrae l'utente dal token JWT nell'header Authorization: Bearer <token>
    """
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Token mancante o non valido")

    token = authorization.split(" ")[1]

    try:
        payload = jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise HTTPException(status_code=401, detail="Token non valido")
    except JWTError as e:
        raise HTTPException(status_code=401, detail=f"Token scaduto o non valido: {e}")

    # Opzionale: verifica che l'utente esista nel DB
    user = users_col.find_one({"username": username})
    if not user:
        raise HTTPException(status_code=401, detail="Utente non trovato")

    return {"username": username}