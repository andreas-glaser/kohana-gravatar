<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Gravatar_Avatar
{

    protected $str_email, $str_rating, $int_image_size, $str_image_default, $bool_image_default_force;
    protected $bool_https = TRUE;

    public static function factory(array $arr_params = array())
    {
        return new Gravatar_Avatar($arr_params);
    }

    public function __construct(array $arr_params = array())
    {
        // execute setup method
        $this->setup($arr_params);

        // return self
        return $this;
    }

    public function setup(array $arr_params = array())
    {
        foreach ($arr_params AS $key => $value)
        {
            $key = strtolower($key);

            if (method_exists($this, $key))
            {
                $this->{$key}($value);
            }
        }

        // return self
        return $this;
    }

    public function reset()
    {
        // reset properties
        $this->str_email = $this->str_rating = $this->int_image_size = $this->str_image_default = $this->bool_image_default_force = NULL;
        $this->bool_https = TRUE;

        // return self
        return $this;
    }

    protected function make_url()
    {
        // validate object
        $this->validate();

        // https / http
        $url = $this->bool_https ? 'https://secure.' : 'http://www.';
        // base url
        $url .= 'gravatar.com/avatar/';
        // hashed email
        $url .= md5($this->str_email);
        // settings
        $url .= URL::query(array(
                    // image size
                    's' => $this->int_image_size,
                    // default image
                    'd' => $this->str_image_default,
                    // image rating
                    'r' => $this->str_rating,
                    // force default imageF
                    'f' => ($this->bool_image_default_force ? 'y' : NULL)
                        ), FALSE
        );

        // return url
        return $url;
    }

    public function url()
    {
        return $this->make_url();
    }

    public function image(array $attributes = NULL, $protocol = NULL, $index = FALSE)
    {
        // set auto attributes
        $arr_attributes_auto = array(
            'width' => $this->int_image_size,
            'height' => $this->int_image_size
        );

        // merge attributes
        $attributes = arr::merge($arr_attributes_auto, (array) $attributes);

        // return html
        return HTML::image($this->make_url(), $attributes, $protocol, $index);
    }

    public function download($str_destination = 'tmp')
    {
        // get tmp direcoty
        if (strtolower($str_destination) === 'tmp' || !$str_destination)
        {
            $str_destination = sys_get_temp_dir();
        }

        $str_destination = Text::reduce_slashes($str_destination . DIRECTORY_SEPARATOR);

        // make sure destination is a directory
        if (!is_dir($str_destination))
        {
            $this->exception('Download destination is not a directory', array(), 100);
        }

        // make sure destination is writeable
        if (!is_writable($str_destination))
        {
            $this->exception('Download destination is not writable', array(), 105);
        }

        // make url
        $str_url = $this->make_url();

        try
        {
            $arr_headers = get_headers($str_url, 1);
        } catch (ErrorException $e)
        {
            if ($e->getCode() === 2)
            {
                $this->exception('URL does not seem to exist', array(), 200);
            }
        }

        $arr_valid_content_types = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif'
        );

        // make sure content type exists
        if (!isset($arr_headers['Content-Type']))
        {
            $this->exception('Download - Content-Type not found', array(), 300);
        }

        // make sure content type is valid
        if (!in_array($arr_headers['Content-Type'], $arr_valid_content_types))
        {
            $this->exception('Download - Content-Type invalid', array(), 305);
        }

        // make sure content disposition exist
        if (isset($arr_headers['Content-Disposition']))
        {
            preg_match('~filename="(.*)"~', $arr_headers['Content-Disposition'], $arr_matches);

            if (!isset($arr_matches[1]))
            {
                $this->exception('Download - Filename not found', array(), 315);
            }

            $str_filename = $arr_matches[1];
        } else
        {
            $str_filename = md5($str_url) . '.' . File::ext_by_mime($arr_headers['Content-Type']);
        }

        try
        {
            file_put_contents($str_destination . $str_filename, file_get_contents($str_url));
        } catch (ErrorException $e)
        {
            $this->exception('Download - File could not been downloaded', array(), 400);
        }

        $result = new stdClass;
        $result->filename = $str_filename;
        $result->extension = File::ext_by_filename($str_filename);
        $result->location = $str_destination . $str_filename;

        return $result;
    }

    public function validate($throw_exceptions = TRUE)
    {
        // init var
        $bool_is_valid = TRUE;

        if (!$this->str_email)
        {
            // set to invalid
            $bool_is_valid = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Email address has not been set');
            }
        }

        if (!$this->str_rating)
        {
            // set to invalid
            $bool_is_valid = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Rating has not been set');
            }
        }

        if (!$this->int_image_size)
        {
            // set to invalid
            $bool_is_valid = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Image size has not been set');
            }
        }

        if (!$this->str_image_default)
        {
            // set to invalid
            $bool_is_valid = FALSE;

            if ($throw_exceptions)
            {
                $this->exception('Default image has not been set');
            }
        }

        if (!$this->str_image_default)
        {
            if ($throw_exceptions)
            {
                $this->exception('Default image has not been set');
            }
        }

        return $bool_is_valid;
    }

    public function https($bool_https)
    {
        // make sure passed image size is integer
        if (!is_bool($bool_https))
        {
            $this->exception('https needs to be TRUE of FALSE');
        }

        // set property
        $this->bool_https = $bool_https;

        // return this
        return $this;
    }

    public function https_true()
    {
        return $this->https(TRUE);
    }

    public function https_false()
    {
        return $this->https(FALSE);
    }

    public function email($str_email)
    {
        // trim leading/trailing white spaces
        $str_email = trim($str_email);

        // make sure passed email address is valid
        if (!Valid::email($str_email))
        {
            $this->exception('Invalid email address passed');
        }

        // force lowercase and set property
        $this->str_email = strtolower($str_email);

        // return this
        return $this;
    }

    public function image_size($int_size)
    {
        // make sure passed image size is integer
        if (!is_int($int_size))
        {
            $this->exception('Image size has to be integer');
        }

        if ($int_size < 1)
        {
            $this->exception('Image size needs to be greater than 0');
        }

        if ($int_size > 2048)
        {
            $this->exception('Image size needs to be smaller or equal 2048');
        }

        // set property
        $this->int_image_size = $int_size;

        // return this
        return $this;
    }

    public function rating($str_rating)
    {
        // list of valid ratings
        $arr_valid_ratings = array('g', 'pg', 'r', 'x');

        // force lowercase and trim leading/trailing white spaces
        $str_rating = trim(strtolower($str_rating));

        // make sure passed rating is valid
        if (!in_array($str_rating, $arr_valid_ratings))
        {
            $this->exception('Invalid rating passed');
        }

        // set property
        $this->str_rating = $str_rating;

        // return this
        return $this;
    }

    public function rating_g()
    {
        return $this->rating('g');
    }

    public function rating_pg()
    {
        return $this->rating('pg');
    }

    public function rating_r()
    {
        return $this->rating('r');
    }

    public function rating_x()
    {
        return $this->rating('x');
    }

    public function image_default($str_image_default)
    {
        // list of valid imagesets
        $arr_valid_image_default_types = array(404, 'mm', 'identicon', 'monsterid', 'wavatar', 'retro', 'blank');

        // trim leading/trailing white spaces
        $str_image_default = trim($str_image_default);

        // is default image a url?
        $bool_is_url = Valid::url($str_image_default);

        if (!$bool_is_url)
        {
            // make sure passed imageset is valid
            if (!in_array($str_image_default, $arr_valid_image_default_types))
            {
                $this->exception('Invalid default image passed (valid: :valid_values', array(':valid_values' => implode(',', $arr_valid_image_default_types)));
            }
        } else
        {
            // encode url
            $str_image_default = urlencode($str_image_default);
        }

        // set property
        $this->str_image_default = $str_image_default;

        // return this
        return $this;
    }

    public function image_default_url($url)
    {
        return $this->image_default($url);
    }

    public function image_default_404()
    {
        return $this->image_default(404);
    }

    public function image_default_mm()
    {
        return $this->image_default('mm');
    }

    public function image_default_identicon()
    {
        return $this->image_default('identicon');
    }

    public function image_default_monsterid()
    {
        return $this->image_default('monsterid');
    }

    public function image_default_wavatar()
    {
        return $this->image_default('wavatar');
    }

    public function image_default_retro()
    {
        return $this->image_default('retro');
    }

    public function image_default_blank()
    {
        return $this->image_default('blank');
    }

    /**
     * Forces gravatar to display default image
     * 
     * @param boolean $bool_force
     * @return \Gravatar
     */
    public function image_default_force($bool_force)
    {
        // make sure passed image size is integer
        if (!is_bool($bool_force))
        {
            $this->exception('Image size has to be integer');
        }

        // set property
        $this->bool_image_default_force = $bool_force;

        // return this
        return $this;
    }

    public function image_default_force_true()
    {
        return $this->image_default_force(TRUE);
    }

    public function image_default_force_false()
    {
        return $this->image_default_force(FALSE);
    }

    /**
     * Kohana Exception Helper
     * 
     * @param string $message
     * @param array $variables
     * @param integer $code
     * @param Exception $previous
     * @throws Kohana_Exception
     */
    protected function exception($message = "", array $variables = NULL, $code = 0, Exception $previous = NULL)
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