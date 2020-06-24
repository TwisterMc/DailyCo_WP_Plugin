# DailyCo WordPress Plugin

*This plugin was created for a specific need. It should work for anyone if you want to use it, but it's not intended to be a fit for every need.*

The DailyCo WordPress Plugin integrates Daily.co video chat into a WordPress site. Site administrators can add the functionality to any page via short code `[dailyco]` or by using the Daily.Co widget. Users that are logged into WordPress then can initiate Daily.co video chats.

## Known Issues

1. When leaving a meeting where the person that initiated the meeting can't interact with the site until they refresh the page.
2. The rooms are not expiring within 24 hours.

## Requirements:

* WordPress 5.4 or later.
	* Not tested on lower versions but may work.
* Daily.Co Account

## Customization:

This plugin's settings are under `Settings -> DailyCo`. Here you can set:

* Daily.co API key
* Form Heading
* Form Button Text
* Form Sub Text
* Email From Name
* Email Subject
* Email Message

### Email Message Merge Tags

When customizing the email message, there are a few merge tags that can be used to pull in dynamic content.

* `[invitee]` - Person's name that is being invited to the video chat.
* `[requester]` - User that is requesting the video chat.
* `[video_link]` - URL to the Daily.co video chat.
* `[site_info]` - Your site's name and URL.

No HTML is allowed in the email message.

## Daily.Co Notes

* Each Daily.co room expires within 24 hours.
* Each room is public, but there is no public listing of room names.
* Room names are random.
* The limit to the number of people in a room and room features is dependent on the API key

## Email Notes

Emails are sent from WordPress using the built in `wp_mail` functionality. These emails could become problematic.

Some hosting companies restrict usage of this function to prevent abuse and spam. Additionally, spam filters on popular email service providers check incoming emails to monitor if they are sent from authentic mail servers. Default WordPress emails fail this check and sometimes may not even make it to the spam folder. -via [WPBeginner](https://www.wpbeginner.com/plugins/how-to-send-email-in-wordpress-using-the-gmail-smtp-server/)

If this becomes a problem, we may need to look into adding a SMTP plugin.

It's also good to note that the from email address is not editable at this time so if a site does choose to add in an SMTP server, I'll have to make code updates.
