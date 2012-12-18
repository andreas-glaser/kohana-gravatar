# Gravatar module for Kohana 3.3.x

Simple module to retrieve a user's profile image from [Gravatar](https://gravatar.com) based on a given email address.
If the email address cannot be matched with a gravatar account, gravatar will return depending on your settings a random generated image.

## Usage

Display user's gravatar

    echo Gravatar_Avatar::factory(array('email' => 'youremail@address.com))
            ->image_size(500)
            ->https_false()
            ->rating_pg()
            ->image_default_identicon()
            ->image();

Display 64x64 gravatar

    echo Gravatar_Avatar::factory(array('email' => 'youremail@address.com))
            ->image_size(64)
            ->https_false()
            ->rating_pg()
            ->image_default_identicon()
            ->image();

Download user's gravatar

    Gravatar_Avatar::factory(array('email' => 'youremail@address.com))
            ->image_size(500)
            ->https_false()
            ->rating_pg()
            ->image_default_identicon()
            ->download();