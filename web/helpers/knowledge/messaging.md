# Messaging

## Messaging page (`/message.php`)

- Only logged-in users can access messaging.
- The left panel lists the current user's matches.
- Match display names are built from the other user's profile name when available.
- Selecting a match loads messages for that match ordered by oldest first.

## Sending messages

- Messages can only be sent to valid match ids that belong to the current user.
- Submitting a message requires both a selected match id and non-empty message text.
- Invalid or unauthorized match ids redirect with `error=invalid_match`.
- Empty or invalid input redirects with `error=invalid_input`.
- Successful sends redirect back to the same match conversation.
- Messages with phone numbers will be changed to #'s to ensure security and safety in line with site policy.

## Message display

- Messages sent by the current user are labelled `You` and styled differently.
- Messages from the other participant are labelled `Them`.
- If there are no matches, the page tells the user to like profiles first.
- If a match has no messages yet, the page prompts the user to start the conversation.
