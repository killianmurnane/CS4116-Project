-- Table structure for table `messages`
CREATE TABLE `messages` (
  `message_id` int NOT NULL,
  `match_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for table `messages`
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `sender_id` (`sender_id`);

-- AUTO_INCREMENT for table `messages`
ALTER TABLE `messages`
  MODIFY `message_id` int NOT NULL AUTO_INCREMENT;

-- Constraints for table `messages`
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);
