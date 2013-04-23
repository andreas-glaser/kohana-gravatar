<?php

/**
 * Written by Andreas Glaser (http://andreas.glaser.me)
 */
defined('SYSPATH') or die('No direct script access.');

class Kohana_Gravatar
{

    /**
     * Email addres
     *
     * @var string
     */
    protected $email;

    /**
     * Content rating
     *
     * @var string
     */
    protected $rating = 'g';

    /**
     * Image size
     *
     * @var int
     */
    protected $size = 64;

    /**
     * Default image type.
     *
     * @var mixed
     */
    protected $image_default = 'mm';

    /**
     * If default image shall be shown
     * even if user the has an gravatar profile.
     *
     * @var boolean
     */
    protected $default_force = FALSE;

    /**
     * Whether or not to use HTTPS
     *
     * @var boolean
     */
    protected $https = TRUE;

    /**
     * Retuns new \Gravatar_Avatar object
     *
     * @param array $params
     * @return \Gravatar_Avatar
     */
    public static function factory(array $params = array())
    {
        return new Gravatar($params);
    }

    /**
     * Constructor forces execution of $this->setup()
     *
     * @param array $params
     * @return \Gravatar_Avatar
     */
    public function __construct(array $params = array())
    {
        // execute setup method
        if (!empty($params))
        {
            $this->setup($params);
        }

        // return self
        return $this;
    }

    /**
     * Helps to load default settings passed by array.
     *
     * @param array $params
     * @return \Gravatar_Avatar
     */
    public function setup(array $params)
    {
        // email
        if (isset($params['email']))
        {
            $this->email_set($params['email']);
        }

        // size
        if (isset($params['size']))
        {
            $this->size_set($params['size']);
        }

        // https
        if (isset($params['https']))
        {
            $this->https_set($params['https']);
        }

        // rating
        if (isset($params['rating']))
        {
            $this->rating_set($params['rating']);
        }

        // force default
        if (isset($params['default']))
        {
            $this->default_set($params['default']);
        }

        // force default
        if (isset($params['default_force']))
        {
            $this->default_force($params['default_force']);
        }

        // return self
        return $this;
    }

    /**
     * Resets all properties. This functon helps to reuse object for another gravatar request.
     *
     * @return \Gravatar_Avatar
     */
    public function reset()
    {
        // reset properties
        $this->email = $this->rating = $this->size = $this->image_default = $this->default_force = NULL;
        $this->https = TRUE;

        // return self
        return $this;
    }

    /**
     * Returns gravatar URL based on passed settings.
     *
     * @throws Kohana_Exception
     * @return string
     */
    protected function url_make()
    {
        // validate object
        $this->validate();

        // https / http
        $url = $this->https ? 'https://secure.' : 'http://www.';
        // base url
        $url .= 'gravatar.com/avatar/';
        // hashed email
        $url .= md5($this->email);
        // settings
        $url .= URL::query(array(
                    // image size
                    's' => $this->size,
                    // default image
                    'd' => $this->image_default,
                    // image rating
                    'r' => $this->rating,
                    // force default imageF
                    'f' => ($this->default_force ? 'y' : NULL)
                        ), FALSE
        );

        // return url
        return $url;
    }

    /**
     * Public function returning $this->url_make();
     *
     * @return string
     */
    public function url()
    {
        return $this->url_make();
    }

    /**
     * Returns html code e.g.
     * <img src="htp://someurl" />
     *
     * @param array $attributes
     * @param boolean $protocol
     * @param boolean $index
     * @return string
     */
    public function image(array $attributes = NULL, $protocol = NULL, $index = FALSE)
    {
        // set auto attributes
        $attributes_auto = array(
            'width' => $this->size,
            'height' => $this->size
        );

        // merge attributes
        $attributes = Arr::merge($attributes_auto, (array) $attributes);

        // return html
        return HTML::image($this->url_make(), $attributes, $protocol, $index);
    }

    /**
     * Downloads gravatar to location on server. Defaults to tmp directory.
     *
     * @param mixed $destination
     * @throws Kohana_Exception
     * @return \stdClass
     */
    public function download($destination = NULL)
    {
        // get tmp direcoty if no destination passed
        if (!$destination)
        {
            $destination = sys_get_temp_dir();
        }

        $destination = Text::reduce_slashes($destination . DIRECTORY_SEPARATOR);

        // make sure destination is a directory
        if (!is_dir($destination))
        {
            $this->exception('Download destination is not a directory', array(), 100);
        }

        // make sure destination is writeable
        if (!is_writable($destination))
        {
            $this->exception('Download destination is not writable', array(), 105);
        }

        // make url
        $url = $this->url_make();

        try
        {
            $headers = get_headers($url, 1);
        } catch (ErrorException $e)
        {
            if ($e->getCode() === 2)
            {
                $this->exception('URL does not seem to exist', array(), 200);
            }
        }

        $valid_content_types = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif'
        );

        // make sure content type exists
        if (!isset($headers['Content-Type']))
        {
            $this->exception('Download - Content-Type not found', array(), 300);
        }

        // make sure content type is valid
        if (!in_array($headers['Content-Type'], $valid_content_types))
        {
            $this->exception('Download - Content-Type invalid', array(), 305);
        }

        // make sure content disposition exist
        if (isset($headers['Content-Disposition']))
        {
            preg_match('~filename="(.*)"~', $headers['Content-Disposition'], $matches);

            if (!isset($matches[1]))
            {
                $this->exception('Download - Filename not found', array(), 315);
            }

            $filename = $matches[1];
        } else
        {
            $filename = md5($url) . '.' . File::ext_by_mime($headers['Content-Type']);
        }

        try
        {
            file_put_contents($destination . $filename, file_get_contents($url));
        } catch (ErrorException $e)
        {
            $this->exception('Download - File could not been downloaded', array(), 400);
        }

        $result = new stdClass;
        $result->filename = $filename;
        $result->extension = File::ext_by_mime($headers['Content-Type']);
        $result->location = $destination . $filename;

        return $result;
    }

    /**
     * Checks whether all necessary properties have been set correclty.
     *
     * @param boolean $throw_exceptions
     * @throws Kohana_Exception
     * @return boolean
     */
    public function validate($throw_exceptions = TRUE)
    {
        // init var
        $valid_is = TRUE;

        if (!$this->email)
        {
            // set to invalid
            $valid_is = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Email address has not been set');
            }
        }

        if (!$this->rating)
        {
            // set to invalid
            $valid_is = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Rating has not been set');
            }
        }

        if (!$this->size)
        {
            // set to invalid
            $valid_is = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Image size has not been set');
            }
        }

        if (!$this->image_default)
        {
            // set to invalid
            $valid_is = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Default image has not been set');
            }
        }

        if (!$this->image_default)
        {
            if ($throw_exceptions)
            {
                $this->exception('Default image has not been set');
            }
        }

        return $valid_is;
    }

    /**
     * Sets used email address.
     *
     * @param string $email
     * @throws Kohana_Exception
     * @return \Gravatar_Avatar
     */
    public function email_set($email)
    {
        // trim leading/trailing white spaces
        $email = trim($email);

        // make sure passed email address is valid
        if (!Valid::email($email))
        {
            $this->exception('Invalid email address passed');
        }

        // force lowercase and set property
        $this->email = strtolower($email);

        // return self
        return $this;
    }

    /**
     * Returns set email address.
     *
     * @return string
     */
    public function email_get()
    {
        return $this->email;
    }

    /**
     * Sets returnes image size.
     *
     * @param integer $size
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function size_set($size)
    {
        // make sure passed image size is integer
        if (!is_int($size))
        {
            $this->exception('Image size has to be integer');
        }

        // make sure passed image size is larger than 0
        if ($size < 1)
        {
            $this->exception('Image size needs to be greater than 0');
        }

        // make sure passed image size is smaller or equal 2048
        if ($size > 2048)
        {
            $this->exception('Image size needs to be smaller or equal 2048');
        }

        // set property
        $this->size = $size;

        // return self
        return $this;
    }

    /**
     * Returns set image size.
     *
     * @return mixed
     */
    public function size_get()
    {
        return $this->size;
    }

    /**
     * Sets content rating.
     *
     * @param string $rating
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar_Avatar
     */
    public function rating_set($rating)
    {
        // list of valid ratings
        $valid_ratings = array('g', 'pg', 'r', 'x');

        // force lowercase and trim leading/trailing white spaces
        $rating = trim(strtolower($rating));

        // make sure passed rating is valid
        if (!in_array($rating, $valid_ratings))
        {
            $this->exception('Invalid rating passed');
        }

        // set property
        $this->rating = $rating;

        // return self
        return $this;
    }

    /**
     * Returns rating.
     *
     * @return string
     */
    public function rating_get()
    {
        return $this->rating;
    }

    /**
     * Sets content rating to G
     *
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar_Avatar
     */
    public function rating_set_g()
    {
        return $this->rating_set('g');
    }

    /**
     * Sets content rating to PG
     *
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar_Avatar
     */
    public function rating_set_pg()
    {
        return $this->rating_set('pg');
    }

    /**
     * Sets content rating to R
     *
     * @return \Kohana_Gravatar_Avatar
     */
    public function rating_set_r()
    {
        return $this->rating_set('r');
    }

    /**
     * Sets content rating to X
     *
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar_Avatar
     */
    public function rating_set_x()
    {
        return $this->rating_set('x');
    }

    /**
     * Sets default image if the user has no gravatar profile.
     *
     * @param string $image_default
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set($image_default)
    {
        // list of valid imagesets
        $valid_image_default_types = array(404, 'mm', 'identicon', 'monsterid', 'wavatar', 'retro', 'blank');

        // trim leading/trailing white spaces
        $image_default = trim($image_default);

        // is default image a url?
        $is_url = Valid::url($image_default);

        if (!$is_url)
        {
            // make sure passed imageset is valid
            if (!in_array($image_default, $valid_image_default_types))
            {
                $this->exception('Invalid default image passed (valid: :valid_values', array(':valid_values' => implode(',', $valid_image_default_types)));
            }
        } else
        {
            // encode url
            $image_default = urlencode($image_default);
        }

        // set property
        $this->image_default = $image_default;

        // return self
        return $this;
    }

    /**
     * Returns $this->image_default;
     *
     * @return string
     */
    public function image_default_get()
    {
        return $this->image_default;
    }

    /**
     * Sets default image to url.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_url($url)
    {
        return $this->default_set($url);
    }

    /**
     * Sets default image to 404.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_404()
    {
        return $this->default_set(404);
    }

    /**
     * Sets default image to mm.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_mm()
    {
        return $this->default_set('mm');
    }

    /**
     * Sets default image to identicon.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_identicon()
    {
        return $this->default_set('identicon');
    }

    /**
     * Sets default image to monsterid.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_monsterid()
    {
        return $this->default_set('monsterid');
    }

    /**
     * Sets default image to wavatar.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_wavatar()
    {
        return $this->default_set('wavatar');
    }

    /**
     * Sets default image to retro.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_retro()
    {
        return $this->default_set('retro');
    }

    /**
     * Sets default image to blank.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_set_blank()
    {
        return $this->default_set('blank');
    }

    /**
     * Forces gravatar to display default image.
     *
     * @param boolean $force
     * @throws Kohana_Exception
     * @return \Gravatar
     */
    public function default_force_set($force)
    {
        // make sure passed image size is integer
        if (!is_bool($force))
        {
            $this->exception('Image size has to be integer');
        }

        // set property
        $this->default_force = $force;

        // return self
        return $this;
    }

    /**
     * Forces default image.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_force_set_true()
    {
        return $this->default_force_set(TRUE);
    }

    /**
     * Disabled forcing of default image.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_force_set_false()
    {
        return $this->default_force_set(FALSE);
    }

    /**
     * Sets whether https ot http should be used to query image.
     *
     * @param boolean $enabled
     * @throws Kohana_Exception
     * @return \Gravatar_Avatar
     */
    public function https_set($enabled)
    {
        // make sure passed image size is integer
        if (!is_bool($enabled))
        {
            $this->exception('https needs to be TRUE of FALSE');
        }

        // set property
        $this->https = $enabled;

        // return self
        return $this;
    }

    /**
     * Enabled https.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function https_set_true()
    {
        return $this->https_set(TRUE);
    }

    /**
     * Disables https.
     *
     * @param string $url
     * @throws Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function https_set_false()
    {
        return $this->https_set(FALSE);
    }

    /**
     * Kohana Exception Helper
     *
     * @param string $message
     * @param array $variables
     * @param int $code
     * @param Exception $previous
     * @throws Kohana_Exception
     */
    protected function exception($message = '', array $variables = NULL, $code = 0, Exception $previous = NULL)
    {
        // prepend string
        $message = 'Gravatar: ' . $message;

        throw new Kohana_Exception($message, $variables, $code, $previous);
    }

    public function __toString()
    {
        return $this->image();
    }

}

