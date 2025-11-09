# Welcome to XRPL Quiz!!
Ready to learn about blockchain and XRP Ledger?

Play a daily quiz and compete with others to prove your knowledge!!

10 questions: 7 about general topics and 3 about the blockchain world!

But don't worry if you are new, this is exactly for you!

Before and after every specific question there will be a short description to introduce you to the topic, and after you will have an explanation of the solution!!

Keep on learning!!! 

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



# TO DOs (for future releases):
* More (randomized) questions
* Insert quesions in a database
* Insert timer also in the description panel
* Minor Fix: Change color of the description panel and the description visualization
* Correct logic behind the prize division: jackpot and consolation prize are divided among all users that answer correctly up to a certain percentage
* Answer description of Question 10 showing 11/10
* Balance button showing a rectangle
* Change box with the proof details to another color since "Proof" is not readable
* Add a "home button" after the quiz or a message showing "Come back tomorrow for another quiz"
* Show leaderboards and other scores