CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  type ENUM('user','admin') DEFAULT 'user',
  status ENUM('active','suspended','banned') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE profiles (
  profile_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  given_name VARCHAR(26),
  family_name VARCHAR(26) NOT NULL,
  gender ENUM('male','female','nonbinary','other','prefer_not') DEFAULT 'prefer_not',
  dob DATE,
  location VARCHAR(120),
  seeking ENUM('male','female','nonbinary','any') DEFAULT 'any',
  description TEXT,
  preferred_sessions TEXT,
  photo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


CREATE TABLE activity (
  activity_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)

  CHECK (liker_id <> liked_id)
) ENGINE=InnoDB;


CREATE TABLE matches (
  match_id INT AUTO_INCREMENT PRIMARY KEY,
  user1_id INT NOT NULL,
  user2_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user1_id) REFERENCES users(user_id),
  FOREIGN KEY (user2_id) REFERENCES users(user_id),

  CHECK (user1_id <> user2_id),
  UNIQUE (user1_id, user2_id)
) ENGINE=InnoDB;


CREATE TABLE messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  message_text TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (match_id) REFERENCES matches(match_id),
  FOREIGN KEY (sender_id) REFERENCES users(user_id),
  FOREIGN KEY (receiver_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


CREATE TABLE likes (
  liker_id INT,
  liked_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (liker_id, liked_id),

  FOREIGN KEY (liker_id) REFERENCES users(user_id),
  FOREIGN KEY (liked_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


CREATE TABLE goals (
  goal_id INT AUTO_INCREMENT PRIMARY KEY,
  goal_name VARCHAR(256) NOT NULL
) ENGINE=InnoDB;


CREATE TABLE user_goals (
  user_id INT,
  goal_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, goal_id),

  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (goal_id) REFERENCES goals(goal_id)
) ENGINE=InnoDB;


CREATE TABLE exercises (
  exercise_id INT AUTO_INCREMENT PRIMARY KEY,
  exercise_name VARCHAR(256) NOT NULL
) ENGINE=InnoDB;


CREATE TABLE user_exercises (
  user_id INT,
  exercise_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, exercise_id),

  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id)
) ENGINE=InnoDB;


CREATE TABLE personal_records (
  pr_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  exercise_id INT,
  weight INT,
  reps INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id)
) ENGINE=InnoDB;