# Gravatar module for Kohana 3.3.x

Simple module to retrieve a users avatar photos from [Gravatar](https://gravatar.com) based on a given email address.

## Usage

Show a users gravatar photo or a randomly generated photo

    echo Gravatar_Avatar::factory(array('email' => 'youremail@address.com))
            ->image_size(500)
            ->https_false()
            ->rating_pg()
            ->image_default_identicon()
            ->image();

You can also download the users avatar

    Gravatar_Avatar::factory(array('email' => 'youremail@address.com))
            ->image_size(500)
            ->https_false()
            ->rating_pg()
            ->image_default_identicon()
            ->download();