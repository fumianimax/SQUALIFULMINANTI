# :zap: Welcome to XRPL Quiz! :shark:
A project of UniTN2 team at <b>IXH 25 - Italian XRPL Hackathon</b> at University of Roma3.

## Ready to learn about blockchain and XRP Ledger?

* Play a daily quiz and compete with others to prove your knowledge!!

* 10 questions: 7 about general topics and 3 about the blockchain world!

## But don't worry! If you are new, this is exactly how you can learn!

* Before and after every specific question there will be a short description to introduce you to the topic, and after you will have an explanation of the solution!!

* Keep on learning!!! 

# How to run the project

1. Some prerequisites: Python 3.10+ (3.11 recommended) • Node 18+ and npm or pnpm • MongoDB running locally (mongodb://127.0.0.1:27017) or a cloud URI • Internet access to XRPL Testnet (https://s.altnet.rippletest.net:51234)

2. Open a new foder and in your terminal run (to clone this repo):
    - git clone https://github.com/fumianimax/SQUALIFULMINANTI.git QUIZ

3. Backend

    3.1 Create and activate a venv
    - cd QUIZ/backend
    - python3 -m venv .venv
    - source .venv/bin/activate 
    
    3.1.5 At any time, to stop, delete and re-run your venv:
    - deactivate
    - rm -rf .venv
    - re-run: back to point 3.1

	3.2 Install dependencies
	- pip install -U pip
	- pip install -r requirements.txt

  	3.4 Start the server backend and LEAVE THIS TERMINAL OPEN!!
	- uvicorn main:app --reload --port 8000

4. Frontend: open a second terminal in the root of the project:
    - cd QUIZ/frontend
    - php -S 127.0.0.1:8080

5. Open the browser and Enjoy the "XRPL QUIZ"!
    - Go to: http://127.0.0.1:8080

# TO DOs (for future releases):
## Graphic Design
* Insert timer also in the description panel
* [FIXED] Improve the description visualization for better readibility
* [FIXED] Answer description of Question 10 showing 11/10
* Balance button showing a rectangle
* Change box with the proof details to another color since "Proof" is not readable
* Add a "home button" after the quiz or a message showing "Come back tomorrow for another quiz"
## Improvements and New Features
* Show leaderboards and other scores
* Correct logic behind the prize division: jackpot and consolation prize are divided among all users that answer correctly up to a certain percentage
* More (randomized) questions
* Insert quesions in a database

# :zap: THE TEAM :zap: - :shark: LIGHTNING SHARKS :shark:
* Barbieri Edoardo
* Marco Battagliola
* Diago Ester
* Fumiani Massimo
* Oddone Maria Caterina
* Pistocchio Aurora
* Pusceddu Marco Egidio