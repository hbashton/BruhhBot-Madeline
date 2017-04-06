# [BruhhBotV2.0](https://telegram.me/BruhhBotV2)

BruhhBotV2.0 is a supergroup manager bot, based on [Daniil's](https://github.com/danog/MadelineProto) MadelineProto implementation for the Telegram Bot API

## Getting Started

To begin using BruhhBot, you will first need to have PHP 7 installed, and set as the default (see below). You will also need php7.0-xml and php7.0-mbstring.

You will also need to register your account and retrieve your Telegram MTProto API and ID [here](https://my.telegram.org/apps)

Install php and it's required extensions

```
sudo apt-get install -y composer php7.0 php7.0-common php7.0-mbstring php7.0-xml
```
### Installing

To deploy BruhhBot, let's clone it to the computer we want to use it on

```
git clone --recursive https://github.com/hbashton/BruhhBot-Madeline.git
```

Create a .env file using [the one provided](.env.example) as a template

Grab your weather API key using [OpenWeather](https://openweathermap.org/api) and place in the .env file

Obtain an API key from [@BotFather](http://telegram.me/botfather)


Finally, deploy your bot

```
./init.sh
```
## FAQ

What do the values in [.env.example](.env.example) stand for?

Well, here's a breakdown:

| Item          | Type           | Description  |
| ------------- |:--------------:| ------------:|
`MTPROTO_NUMBER` | (int) | The phone number you used to sign the bot up to telegram
`MTPROTO_SETTINGS` | (json) | Your API ID and HASH (I'm not using these right now, but I require them if I decide to do so in the future
`MASTER_USERNAME` | (string) | Your username
`SUDO` | (array) |An array of User ID's that are considered "sudo" users, and can control the bot just like you
`WEATHER_KEY` | (string) | Obtained from [OpenWeather](https://openweathermap.org/api)
`BOT_USERNAME` | (string) | The username of your bot created with a phone number
`BOT_TOKEN` | (string) | Your API token received from [@BotFather](http://telegram.me/botfather)
`BOT_API_USERNAME` | (string) | The username you assigned to your bot from [@BotFather](http://telegram.me/botfather)
## PLEASE READ THIS!

Your session, where you're logged in, is saved in session.madeline and bot.madeline (in the root of your bot directory).
Sometimes, inevitably, the bot gets killed before fully writing to the file. In this case, you will need to remove it and rerun the bot.

## Support

For support, message [@hunter_bruhh](https://telegram.me/hunter_bruhh) on telegram < (or just click the username)

## Built With

* [c9](https://c9.io) - The cloud IDE everyone needs
* [MadelineProto](https://github.com/danog/MadelineProto) - What makes this whole project possible

## Authors

* **Hunter Ashton** - *Primary developer* - [@hbashton](https://github.com/hbashton)

* **Daniil Gentili** - *The handyman* - [@danog](https://github.com/danog)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

* [@MSF-Jarvis](https://github.com/msf-jarvis) thank you for your ideas, love, and support on the days I need it the most.
* [@xdevs23](https://github.com/xdevs23) thank you for everything, as a fellow team member and friend without your support I couldn't do stuff like this
* [@nicholaschum](https://github.com/nicholaschum) you push me to be the best I can be, and I can't begin to thank you.
* [@danog](https://github.com/danog) OK yes I mentioned him as the handyman and as an author, but he needs an acknowledgement too. I LOVE YOU MAN!!!! WE DID GREAT
