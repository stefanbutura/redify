![Redify](https://i.imgur.com/FruI9mq.png)

# What is redify

A php tool used to sync time entries between Redmine and Clockify. Time entries are fetched from Redmine for each configured user and added into their respective Clockify.

# Prerequisites

1. Create a database

2. Copy config.example.yml into config.yml and edit it

    * Add your Redmine instances
    
    * Edit the Clockify workspace ID

    * Edit your database connection

3. Patch your Redmine instances using patch/time-entry-query.patch. This makes Redmine support updated_on queries for time entries - mandatory for fetching time entries updated after a certain date.

4. Add the custom field "Clockify key" for users in your Redmine instance. Only users that fill this field will have their time entries synced.

5. Add the custom field "Clockify project ID" for projects in your Redmine instance. Time entries will only be synced for projects that have this field.

6. Add the custom field "Clockify workspace ID" for projects in your Redmine instance. Time entries will only be synced for projects that have this field.


# How to run

`php redify.php`

Ideally, this command should be run daily, on cron. After each sync, the last updated date is stored in the redify database. Each subsequent run only fetches time entries after the last run.

The first run of this command will only fetch today's time entries.

# Fetching older time entries

`mysql -u root -p -D {DB_NAME} -e "UPDATE variables SET value = '2000-01-25' WHERE ID = 'last_run';"`

`php redify.php`

# Known issues

1. Deleting a time entry from Redmine will not delete the time entry from Clockify. For now, it has to be deleted manually.

2. Only the most recent 100 entries are updated for each user
