def get_user(db, email):
    return db.users.find_one({"email": email})

def create_user(db, email, wallet):
    db.users.insert_one({"email": email, "wallet": wallet, "score": 0})
