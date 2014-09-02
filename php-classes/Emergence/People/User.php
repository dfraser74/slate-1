<?php

namespace Emergence\People;

use DB;

class User extends Person
{
    public static $minPasswordLength = 5;

    public static $defaultClass = __CLASS__;
    public static $subClasses = array(__CLASS__);
    public static $singularNoun = 'user';
    public static $pluralNoun = 'users';

    // ActiveRecord configuration
    public static $fields = array(
        'Username' => array(
            'unique' => true
            ,'includeInSummary' => true
        )
        ,'Password' => array(
            'type' => 'string'
            ,'excludeFromData' => true
        )
        ,'AccountLevel' => array(
            'type' => 'enum'
            ,'values' => array('Disabled','Contact','User','Staff','Administrator','Developer')
            ,'default' => 'User'
        )
    );

    public static $searchConditions = array(
        'Username' => array(
            'qualifiers' => array('any','username','uname','user')
            ,'points' => 3
            ,'sql' => 'Username LIKE "%%%s%%"'
        )
        ,'AccountLevel' => array(
            'qualifiers' => array('accountlevel')
            ,'points' => 2
            ,'sql' => 'AccountLevel LIKE "%%%s%%"'
        )
    );

    public static $validators = array(
        'Username' => array(
            'validator' => 'handle'
            ,'required' => true
            ,'errorMessage' => 'Username can only contain letters, numbers, hyphens, and underscores.'
        )
        ,'AccountLevel' => array(
            'validator' => 'selection'
            ,'choices' => array() // filled dynamically in __classLoaded
            ,'required' => false
        )
    );

    public static function __classLoaded()
    {
        // merge User classes into valid Person classes, but not again when child classes are loaded
        if (get_called_class() == __CLASS__) {
            Person::$subClasses = static::$subClasses = array_merge(Person::$subClasses, static::$subClasses);
            self::$validators['AccountLevel']['choices'] = self::$fields['AccountLevel']['values'];
        }

        // finish ActiveRecord initialization
        parent::__classLoaded();
    }

    function getValue($name)
    {
        switch ($name) {
            case 'AccountLevelNumeric':
                return static::_getAccountLevelIndex($this->AccountLevel);
            case 'Handle':
                return $this->Username;
            default:
                return parent::getValue($name);
        }
    }

    public function validate($deep = true)
    {
        // call parent
        parent::validate($deep);

        // check username uniqueness
        if ($this->isDirty && !$this->_validator->hasErrors('Username') && $this->Username) {
            $ExistingUser = static::getByUsername($this->Username);

            if ($ExistingUser && ($ExistingUser->ID != $this->ID)) {
                $this->_validator->addError('Username', 'Username already registered.');
            }
        }

        // save results
        return $this->finishValidation();
    }

    public function save($deep = true)
    {
        if (!$this->Username) {
            $this->Username = static::getUniqueUsername($this->FirstName, $this->LastName);
        }

        return parent::save($deep);
    }

    public function getHandle()
    {
        return $this->Username;
    }

    public static function getByHandle($handle)
    {
        return static::getByUsername($handle);
    }

    // enable login by email
    public static function getByLogin($username, $password)
    {
        $User = static::getByUsername($username);

        if ($User && is_a($User, __CLASS__) && $User->hasAccountLevel('User') && $User->verifyPassword($password)) {
            return $User;
        } else {
            return null;
        }
    }

    public static function getByUsername($username)
    {
        // try to get by username first
        $User = static::getByWhere(array('Username' => $username));
        if (!$User && !\Validators\EmailAddress::isInvalid($username)) {
            $EmailPoint = \Emergence\People\ContactPoint\Email::getByString($username);
            $User = $EmailPoint->Person;
        }

        return $User;
    }

    public function verifyPassword($password)
    {
        if ($this->Password[0] == '$') {
            return password_verify($password, $this->Password);
        } elseif (SHA1($password) == $this->Password) {
            $wasDirty = $this->isDirty;
            $this->setClearPassword($password);

            if (!$wasDirty) {
                $this->save();
            }

            return true;
        }

        return false;
    }

    public function setClearPassword($password)
    {
        $this->Password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function hasAccountLevel($accountLevel)
    {
        $accountLevelIndex = static::_getAccountLevelIndex($accountLevel);

        if ($accountLevelIndex === false) {
            return false;
        } else {
            return ($this->AccountLevelNumeric >= $accountLevelIndex);
        }
    }

    public static function getUniqueUsername($firstName, $lastName, $options = array())
    {
        // apply default options
        $options = array_merge(array(
            'format' => 'short' // full or short
        ), $options);

        // create username
        switch ($options['format']) {
            case 'short':
                $username = $firstName[0].$lastName;
                break;
            case 'full':
                $username = $firstName.'_'.$lastName;
                break;
            default:
                throw new Exception ('Unknown username format.');
        }

        // strip bad characters
        $username = $strippedText = preg_replace(
            array('/\s+/', '/[^a-zA-Z0-9\-_]+/')
            , array('_', '-')
            , strtolower($username)
        );

        $incarnation = 1;
        while (static::getByWhere(array('Username'=>$username))) {
            // TODO: check for repeat posting here?
            $incarnation++;

            $username = $strippedText . $incarnation;
        }

        return $username;
    }

    protected static function _getAccountLevelIndex($accountLevel)
    {
        return array_search($accountLevel, self::$fields['AccountLevel']['values']);
    }
}