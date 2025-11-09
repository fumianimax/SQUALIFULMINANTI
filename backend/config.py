import os
from dotenv import load_dotenv

load_dotenv()

# Connections and Security
MONGO_URL = os.getenv("MONGO_URL", "mongodb://localhost:27017")
JWT_SECRET = os.getenv("JWT_SECRET", "supersecretjwtkey")
JWT_ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")

# XRPL Node
XRPL_NODE_URL = os.getenv(
    "XRPL_NODE_URL",
    "wss://s.altnet.rippletest.net:51233"
)

# Other Settings
DEBUG = os.getenv("DEBUG", "True").lower() == "true"
APP_NAME = os.getenv("APP_NAME", "XRPL Quiz App")