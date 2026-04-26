The dashboard greets the user by their full name when available otherwise by email. It shows profile completion percentage quick actions to search message and edit the profile plus previews of selected goals and exercises. The dashboard also displays activity counts for matches total messages inside matched conversations likes sent and likes received.

Profile completion is based on whether the following are present: given name, family name, gender, location, date of birth, bio or description, preferred sessions, at least one goal, and at least one exercise.

Users can view their own profile or another user's profile. To view another user's profile you POST their user id to the profile page. If a user record is not found the page redirects to 404. The profile page shows email location gender date of birth preferred sessions goals and exercises. Your own profile view includes actions to edit the profile search for partners and open messages. Viewing someone else's profile shows navigation back to search and back to your own profile.

Only logged-in users can edit their own profile. Given name is required. Gender must be one of the allowed values if provided. Location must be a valid location if provided. Date of birth must be a valid date if provided. Users can add or remove multiple goals and exercises. Updating the profile also replaces the user's goal and exercise associations. Changes are wrapped in a database transaction.

Users can complete their profile by filling in the fields in the edit profile page which is accessed from the profile page.
