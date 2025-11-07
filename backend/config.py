import os
from dotenv import load_dotenv

# carica le variabili dal file .env (se esiste)
load_dotenv()

# Sicurezza & connessioni
MONGO_URL = os.getenv("MONGO_URL", "mongodb://localhost:27017")
JWT_SECRET = os.getenv("JWT_SECRET", "supersecretjwtkey")
JWT_ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")

# Nodo XRPL
XRPL_NODE_URL = os.getenv(
    "XRPL_NODE_URL",
    "wss://s.altnet.rippletest.net:51233"
)

# Altre impostazioni opzionali
DEBUG = os.getenv("DEBUG", "True").lower() == "true"
APP_NAME = os.getenv("APP_NAME", "XRPL Quiz App")