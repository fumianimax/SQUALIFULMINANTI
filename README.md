# How to run the project

* 1. Some prerequisites: Python 3.10+ (3.11 recommended) • Node 18+ and npm or pnpm • MongoDB running locally (mongodb://127.0.0.1:27017) or a cloud URI • Internet access to XRPL Testnet (https://s.altnet.rippletest.net:51234)

* 2. Clone this repo:
- https://github.com/fumianimax/SQUALIFULMINANTI.git

* 3. Backend
    - 3.1 Create and activate a venv
    - cd backend
    - python3 -m venv .venv
    - source .venv/bin/activate 

	- 3.2 Install dependencies
	- pip install -U pip
	- pip install -r requirements.txt

  	- 3.3 Run MongoDB
  	- Local: ensure mongod is running Atlas: put your connection string into MONGODB_URI

  	- 3.4 Run the server
	- uvicorn main:app --reload --port 8000


* 4. Frontend setup
    - cd frontend
    - php -S 127.0.0.1:8080

* 5. Enjoy
    - Go to: http://127.0.0.1:8080