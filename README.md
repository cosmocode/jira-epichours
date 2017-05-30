# Jira helpers

Some simple PHP command line tools speaking with the Jira API.

To install, check it out and run composer

    composer installer

Then copy the `epichours.ini.dist` to `epichours.ini` and edit it to set the API credentials.
 
## epichours.php

Print the booked hours/days summarized by Epic. Run it with the shortcode of the project. You can also give multiple projects.

    ./epichours.php SPR
    Hours	Days	Epic	Summary
    106.5	13.31	SPR-27	Administration
    16.25	2.03	SPR-84	Implement floobarb
    6.5	0.81	SPR-85	Funky Tunes Integration
    31	3.88	SPR-121	Project Management

## issuetable.php

Creates a list of issues in one or more given Epics and prints it as as DokuWiki formatted table. Additional columns are printed for upcoming calendar weeks. This makes it very simple to create a simple GANTT chart for those issues in DokuWiki.

    ./issuetable.php -c 3 RES-13
    ^  Issue                                            ||  W22  |  W23  |  W24  |
    ^  [[jira>RES-232]] ^ Implement FunkyDoodle          |       |       |       |
    ^  [[jira>RES-233]] ^ make foobar work               |       |       |       |
    ^  [[jira>RES-234]] ^ wobble breaks flubble          |       |       |       |