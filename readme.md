#PTE Bot (Alpha)

Reviewing is boring and sometimes the queue of string to approve can be very long.

Go to [ptebot.mte90.net](http://ptebot.mte90.net) and insert your email to receive a random plugin 1, 2 or 3 times a week that need an approval of the strings.

##How Works

Every day check the frequency for every PTE subscribed and pick a random plugin what have strings not approved.  
Actually do a scraping from the HTML because GlotPress don't have a REST API so this pick 1 plugin from a list of 20 plugins.

# Install

* Create in the backend the language supported as category with the slug used on wordpress.org
* Use the shortcode [ptebot-signup] to show the registration form
* Choose how to manage the emails with the WP Cron

#New language

The system it is on my hosting so I want avoid an huge number of emails, so I can add languages only by request in this repo with a ticket.  
I hope for an external system to move when this will be avalaible for all the community.
