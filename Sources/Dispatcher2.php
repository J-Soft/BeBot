<?php
/**
 * +-----------------------------------------------------------------------+
 * | Copyright (c) 2009, Dmitri Snytkine                                 |
 * | All rights reserved.                                                  |
 * |                                                                       |
 * | Redistribution and use in source and binary forms, with or without    |
 * | modification, are permitted provided that the following conditions    |
 * | are met:                                                              |
 * |                                                                       |
 * | o Redistributions of source code must retain the above copyright      |
 * |   notice, this list of conditions and the following disclaimer.       |
 * | o Redistributions in binary form must reproduce the above copyright   |
 * |   notice, this list of conditions and the following disclaimer in the |
 * |   documentation and/or other materials provided with the distribution.|
 * | o The names of the authors may not be used to endorse or promote      |
 * |   products derived from this software without specific prior written  |
 * |   permission.                                                         |
 * |                                                                       |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
 * | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
 * | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
 * | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
 * | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
 * | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
 * | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
 * | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * | Author: Bertrand Mansion <bmansion@mamasam.com>                       |
 * |         Stephan Schmidt <schst@php.net>                               |
 * |         Dmitri Snytkine  <d.snytkine@gmail.com>                       |
 * +-----------------------------------------------------------------------+
 *
 * $Id: Dispatcher.php,v 1.4 2009/03/14
 * Dispatch notifications using PHP callbacks
 *
 * The Event_Dispatcher acts acts as a notification dispatch table.
 * It is used to notify other objects of interesting things, if
 * they meet certain criteria. This information is encapsulated
 * in {@link Event_Notification2} objects. Client objects register
 * themselves with the Event_Dispatcher2 as observers of specific
 * notifications posted by other objects. When an event occurs,
 * an object posts an appropriate notification to the Event_Dispatcher2.
 * The Event_Dispatcher2 dispatches a message to each
 * registered observer, passing the notification as the sole argument.
 *
 * The Event_Dispatcher2 is actually a combination of three design
 * patterns: the Singleton, {@link http://c2.com/cgi/wiki?MediatorPattern Mediator},
 * and Observer patterns. The idea behind Event_Dispatcher2 is borrowed from
 * {@link http://developer.apple.com/documentation/Cocoa/Conceptual/Notifications/index.html
 * Apple's Cocoa framework}.
 *
 * PHP version 5
 *
 * @category  Event
 * @package   Event_Dispatcher2
 * @author    Dmitri Snytkine <d.snytkine@gmail.com>
 * @author    Bertrand Mansion <bmansion@mamasam.com>
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 1997-2009 The PHP Group
 * @license   BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version   SVN: <svn_id>
 * @link      http://pear.php.net/package/Event_Dispatcher2
 * @filesource
 *
 */

/**
 * Base exception class for this package
 *
 * @category  Event
 * @package   Event_Dispatcher2
 * @author    Dmitri Snytkine <d.snytkine@gmail.com>
 * @copyright 1997-2009 The PHP Group
 * @license   BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version   Release: <svn_id>
 * @link      http://pear.php.net/package/Event_Dispatcher2
 *
 */
class Event_Dispatcher_Exception
{

}

/**
 * User exception class
 *
 * @category  Event
 * @package   Event_Dispatcher2
 * @author    Dmitri Snytkine <d.snytkine@gmail.com>
 * @copyright 1997-2009 The PHP Group
 * @license   BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version   Release: <svn_id>
 * @link      http://pear.php.net/package/Event_Dispatcher2
 *
 *
 */
class Event_Dispatcher_User_Exception extends Event_Dispatcher_Exception
{

}


/**
 * Event_Dispatche2 class
 *
 * @category  Event
 * @package   Event_Dispatcher2
 * @author    Dmitri Snytkine <d.snytkine@gmail.com>
 * @copyright 1997-2009 The PHP Group
 * @license   BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version   Release: <svn_id>
 * @link      http://pear.php.net/package/Event_Dispatcher2
 *
 *
 */
class Event_Dispatcher2 implements SplSubject
{

    /**
     * Name of global observer
     * will be used as array key in $this->ro array
     * All global events are stored under this key
     */
    const EVENT_DISPATCHER_GLOBAL = 'EVENT_DISPATCHER_GLOBAL';

    /**
     * array of dispatchers objects
     * @var array
     */
    protected static $dispatchers = array();

    /**
     * Registered observer callbacks
     * @var array
     * @access private
     */
    protected $ro = array();

    /**
     * Pending notifications
     * @var array
     * @access private
     */
    protected $pending = array();

    /**
     * Nested observers
     * @var array
     * @access private
     */
    protected $nestedDispatchers = array();

    /**
     * Name of the dispatcher
     * @var string
     * @access private
     */
    protected $name = null;


    /**
     * Class used for notifications
     * @var string
     * @access private
     */
    protected $notificationClass = null;


    /**
     * PHP5 constructor
     *
     * Please use {@link singleton()} instead.
     *
     * @param string $name              Name of the this
     *                                  notification dispatcher object
     * @param string $notificationClass Name of notification class
     */
    protected function __construct($name, $notificationClass)
    {
        $this->name = $name;
        $this->notificationClass = $notificationClass;
        $this->ro[self::EVENT_DISPATCHER_GLOBAL] = array();
    }


    /**
     * Returns a notification dispatcher singleton
     *
     * There is usually no need to have more than one notification
     * center for an application so this is the recommended way
     * to get a Event_Dispatcher2 object.
     *
     * @param string $name              Name of the
     *                                  notification dispatcher.
     * The default notification dispatcher is named __default.
     *
     * @param string $notificationClass This one is tricky.
     * Its the name of class which will be used an
     * a notification class.
     * By default its an Even_Notification class,
     * which extends this class and because
     * of it it implements the SplSubject interface.
     *
     * The ability to set a different class name gives you
     * the flexibility to use your very own class
     * as an event notification class.
     * If you decide that you really need to write and use
     * your own notification class,
     * then make sure it implements the SplSubject interface
     * and make sure to include the path to
     * your class definition somewhere in your script
     * that uses this class
     * If you have no clue what this means, then
     * leave the default value!
     *
     * @return object Event_Dispatcher2
     */
    public static final function getInstance($name = '__default', $notificationClass = 'Event_Notification2')
    {
        if (!isset(self::$dispatchers[$name]) || !isset(self::$dispatchers[$name][$notificationClass]) ) {
            self::$dispatchers[$name][$notificationClass] = new Event_Dispatcher2($name, $notificationClass);
        }

        return self::$dispatchers[$name][$notificationClass];
    }


    /**
     * Getter for name of notification class
     *
     * @return string name of default notification class
     */
    public function getNotificationName()
    {
        return $this->notificationClass;
    }


    /**
     * For information purposed and for purposes on loggin, so
     * you may add the class to log like $log($oEvent) where $oEvent is this object
     *
     * @return string with info about this object
     */
    public function __toString()
    {
        return 'Instance of Event_Dispatcher2 "_name: "'.$this->getName().' notification object name: '.$this->getNotificationName();
    }


    /**
     * Registers an observer callback
     *
     * This method registers a
     * {@link http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback callback}
     * which is called when the notification corresponding to the
     * criteria given at registration time is posted.
     * The criteria are the notification name and eventually the
     * class of the object posted with the notification.
     *
     * If there are any pending notifications corresponding to the criteria
     * given here, the callback will be called straight away.
     *
     * If the notification name is empty, the observer will receive all the
     * posted notifications. Same goes for the class name.
     *
     * @param mixed  $callback A PHP callback can be name
     *                         of callback function
     *                         or array or object
     * @param string $nName    Expected notification name (for example 'onDbUpdate'),
     *                         serves as a filter
     * @param string $class    Expected contained object class,
     *                         serves as an extra filter
     *                         This means an object will receive notificaton
     * only if its subscribed to a specific event and only to
     * this specific notification object
     * In order to receive all events but only for a
     * specific notification object you must
     * subscribe to global event and pass specific class name,
     * like this: oEvent->addObserver($o, null, 'specificClass')
     *
     * @return object $this
     */
    public function addObserver($callback, $nName = null, $class = null)
    {
        $nName = (null !== $nName) ? $nName : self::EVENT_DISPATCHER_GLOBAL;
        $aCallback = $this->checkCallback($callback);
        extract($aCallback);

        $this->ro[$nName][$reg] = array(
                                    'callback' => $callback,
                                    'class'    => $class
        );

        return $this->postPendingEvents($callback, $nName, $class);
    }


    /**
     * Post eventual pending notifications for this event name ($nName)
     *
     * @param mixed  $callback callback
     * @param string $nName    event name
     * @param string $class    class name
     *
     * @return object $this
     */
    protected function postPendingEvents($callback, $nName = self::EVENT_DISPATCHER_GLOBAL, $class = null)
    {
        if (isset($this->pending[$nName])) {
            foreach ($this->pending[$nName] as $notification) {
                if (!$notification->isNotificationCancelled()) {
                    $objClass = get_class($notification->getNotificationObject());
                    if (empty($class) || strcasecmp($class, $objClass) === 0) {
                        call_user_func_array($callback, array($notification));
                        $notification->increaseNotificationCount();
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Getter for $this->pending array
     *
     * @return array
     */
    public function getPendingEvents()
    {
        return $this->pending;
    }


    /**
     * If your observer implements SplObserver interface its a lot
     * faster to use this method than addObserver() because it bypasses
     * all types of checks like is_array(), is_object(), is_callable()
     *
     * You can still use addObserver($yourobject, $nName, $class)
     * if you want to subscribe to only specific event(s) or to
     * specific class passed in Notification object.
     *
     * Using this method will subscribe your observer object to all events
     * When this object calls your observer's update() method, you can query
     * the object passed in update() to get the notification name, like this:
     * $eventName = $oNotification->getNotificationName();
     * $obj = $oNotification->getNotificationObject();
     * $aInfo = $oNotification->getNotificationInfo();
     *
     * @param object $observer object that implements SplObserver interface
     * which means it must have method update(SplSubject $o)
     * the $o object will be an instance of Event_Notification2 class
     *
     * @return object $this
     */
    public function attach(SplObserver $observer)
    {
        $reg = get_class($observer).'::update';
        $callback = array($observer, 'update');
         
        $this->ro[self::EVENT_DISPATCHER_GLOBAL][$reg] = array(
                                    'callback' => $callback);

        return $this->postPendingEvents($callback);
    }


    /**
     * If your observer was registered using attach() method,
     * then it can be
     * detached (unregistered) using this method
     *
     * @param object $observer Object that implements
     *                         SplObserver interface
     *
     * @return void
     */
    public function detach(SplObserver $observer)
    {
        $reg = get_class($observer).'::update';
        if (array_key_exists(self::EVENT_DISPATCHER_GLOBAL, $this->ro) && isset($this->ro[self::EVENT_DISPATCHER_GLOBAL][$reg])) {
            unset($this->ro[self::EVENT_DISPATCHER_GLOBAL][$reg]);
        }
    }


    /**
     * Posts the {@link Event_Notification2} object
     * Even though this method is public, you should not use it directly,
     * instead use post() method to post new event
     * This method will be invoked from post() with correct params.
     *
     * @param object $notification The Notification object
     * @param bool   $pending      Whether to post the notification immediately
     * @param bool   $bubble       Whether you want the notification to bubble up
     * @param string $objClass     Name of object passed in notification object
     * @param string $nName        Name of notification even
     * (for example 'onTableUpdate' - up to you to name your events)
     *
     * @see Event_Dispatcher2::post()
     *
     * @return  object  The notification object
     */
    public function notify(Event_Notification2 $notification, $pending = true, $bubble = true, $objClass = null, $nName = null)
    {
        $objClass = (null !== $objClass) ? $objClass :  get_class($notification->getNotificationObject());
        $nName = (null !== $nName) ? $nName : $notification->getNotificationName();

        if ( true === $pending ) {
            $this->pending[$nName][] = $notification;
        }

        /**
         * Find the registered observers
         */
        if (isset($this->ro[$nName])) {

            if ($notification->isNotificationCancelled()) {

                return $notification;
            }

            foreach ($this->ro[$nName] as $rObserver) {
                if ( empty($rObserver['class']) || (strcasecmp($rObserver['class'], $objClass) === 0) ) {
                    call_user_func_array($rObserver['callback'], array($notification));
                    $notification->increaseNotificationCount();
                }
            }
        }

        /**
         * Notify globally registered observers
         */
        if ( (self::EVENT_DISPATCHER_GLOBAL !== $nName) && isset($this->ro[self::EVENT_DISPATCHER_GLOBAL])) {
            $this->notify($notification, $pending, $bubble, $objClass, self::EVENT_DISPATCHER_GLOBAL);
        }

        if ( false === $bubble ) {

            return $notification;
        }

        $this->notifyNestedDispatchers();


        return $notification;
    }

    /**
     * calls notify() on nested dispatchers if nested dispatchers exist
     *
     * @return object $this
     */
    protected function notifyNestedDispatchers()
    {
        if (!empty($this->nestedDispatchers)) {
            foreach ($this->nestedDispatchers as $oNested) {
                $notification = $oNested->notify($notification, $pending);
            }
        }

        return $this;
    }

    /**
     * Performes check on $callback string
     *
     * @param mixed $callback can be array, string or object
     * If string, then must be a name of existing function,
     *
     * If object, then it must have public method update().
     * a notification object will be passed
     * to that object's update() method
     *
     * If array, it must have exactly 2 elements:
     * 0 and 1 where 0 is the object or class
     * and 1 is the name of method in that object
     * which will be invoked.
     *
     * @return array with keys 'reg' and 'callback'
     *
     * @throws Event_Dispatcher_User_Exception
     * if something is not right with $callback
     */
    protected function checkCallback($callback)
    {
        if (is_array($callback)) {
            if (!array_key_exists('0', $callback) || !array_key_exists('1', $callback) || (count($callback) > 2) ) {
                throw new Event_Dispatcher_User_Exception('Callback array MUST have exactly 2 elements with keys 0 (with value of class name or object) and 1 (with value of method name)');
            }

            if (is_object($callback[0])) {
                if (!is_callable($callback)) {
                    throw new Event_Dispatcher_User_Exception('callback is object but not a valid callable object/method array');
                }
                $reg = get_class($callback[0]).'::'.$callback[1];
            } else {
                if (!in_array($callback[1], get_class_methods($callback[0])) ) {
                    throw new Event_Dispatcher_User_Exception('Method '.$callback[1].' does not exist in class '.$callback[0]);
                }
                $reg = $callback[0].'::'.$callback[1];
            }
        } elseif (is_string($callback)) {
            if ( !is_callable($callback)) {
                throw new Event_Dispatcher_User_Exception('Callback function '.$callback. ' does not exist or is not a valid callable function');
            }

            $reg = $callback;
        } elseif (is_object($callback)) {
            if ( !method_exists($callback, 'update') || !is_callable(array($callback, 'update')) ) {
                throw new Event_Dispatcher_User_Exception('Callback object must have the update() method');
            }

            $reg = get_class($callback).'::update';
            $callback = array($callback, 'update');
        } else {
            throw new Event_Dispatcher_User_Exception('wrong type of variable $callback - it must be string or array or object');
        }

        return compact('reg', 'callback');
    }


    /**
     * Creates and posts a notification object
     *
     * The purpose of the optional associated object is generally to pass
     * the object posting the notification to the observers, so that the
     * observers can query the posting object for more information about
     * the event.
     *
     * Notifications are by default added to a pending notification list.
     * This way, if an observer is not registered by the time they are
     * posted, it will still be notified when it is added as an observer.
     * This behaviour can be turned off in order to make sure that only
     * the registered observers will be notified.
     *
     * The info array serves as a container for any kind of useful
     * information. It is added to the notification object and posted along.
     *
     * @param object $object  Notification associated object
     * @param string $nName   Notification name
     * @param array  $info    Optional user information
     * @param bool   $pending Whether the notification is pending
     * @param bool   $bubble  Whether you want the notification to bubble up
     *
     * @return object  The notification object acts as an extra filter.
	 *
	 * BeBot specific. Defaults for pending and bubble are now false rather than true.
     */
    public function post($object, $nName, $info = array(), $pending = false, $bubble = false)
    {
        include_once 'Notification2.php';
        $notification = new $this->notificationClass($object, $nName, $info);
        $objClass = get_class($object);

        return $this->notify($notification, $pending, $bubble, $objClass, $nName);
    }


    /**
     * Deprecated.
     * This method has been deprecated
     *
     * @return void
     *
     * @throws Event_Dispatcher_User_Exception because this method is deprecated
     */
    protected function postNotification()
    {
        throw new Event_Dispatcher_User_Exception('This method has been deprecated');
    }


    /**
     * Removes a registered observer that correspond to the given criteria
     *
     * @param mixed  $callback A PHP callback
     * @param string $nName    Notification name
     * @param string $class    Contained object class
     *
     * @return bool    True if an observer was removed, false otherwise
     */
    public function removeObserver($callback, $nName = null, $class = null)
    {
        $nName = (null !== $nName) ? $nName : self::EVENT_DISPATCHER_GLOBAL;
        $aCallback = $this->checkCallback($callback);
        extract($aCallback);

        $removed = false;
        if (isset($this->ro[$nName][$reg])) {
            if (!empty($class)) {
                if (strcasecmp($this->ro[$nName][$reg]['class'], $class) === 0) {
                    unset($this->ro[$nName][$reg]);
                    $removed = true;
                }
            } else {
                unset($this->ro[$nName][$reg]);
                $removed = true;
            }
        }

        if (isset($this->ro[$nName]) && count($this->ro[$nName]) === 0) {
            unset($this->ro[$nName]);
        }

        return $removed;
    }

    /**
     * Check, whether the specified observer has been registered with the
     * dispatcher
     *
     * @param mixed  $callback A PHP callback
     * @param string $nName    Notification name
     * @param string $class    Contained object class
     *
     * @return  bool        True if the observer has been registered, false otherwise
     */
    public function observerRegistered($callback, $nName = self::EVENT_DISPATCHER_GLOBAL, $class = null)
    {
        $aCallback = $this->checkCallback($callback);
        extract($aCallback);

        if (!isset($this->ro[$nName][$reg])) {

            return false;
        }
        if (empty($class)) {

            return true;
        }

        return ( 0 === strcasecmp($this->ro[$nName][$reg]['class'], $class));
    }


    /**
     * Get all observers, that have been registered for a notification
     *
     * @param string $nName Notification name
     * @param string $class Contained object class
     *
     * @return  array       List of all observers
     */
    public function getObservers($nName = self::EVENT_DISPATCHER_GLOBAL, $class = null)
    {
        $observers = array();
        if (!isset($this->ro[$nName])) {

            return $observers;
        }

        foreach ($this->ro[$nName] as $reg => $observer) {
            if ( null === $class ||  null === $observer['class'] ||  0 === strcasecmp($observer['class'], $class) ) {
                $observers[] = $reg;
            }
        }

        return $observers;
    }


    /**
     * Getter for $this->$ro array
     *
     * @return array
     */
    public function getRegisteredObservers()
    {
        return $this->ro;
    }


    /**
     * Get the name of the dispatcher.
     *
     * The name is the unique identifier of a dispatcher.
     *
     * @return string     name of the dispatcher
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a new nested dispatcher
     *
     * Notifications will be broadcasted to this dispatcher as well,
     * which allows you to create event bubbling.
     * this nested dispatcher should actually
     * be higher up in the chain of events
     * for example if you update specific db table,
     * the onSomeTableUpdate
     * is specific event and its nested dispatcher
     * can be onDbUpdate
     *
     * @param object $dispatcher The nested dispatcher
     *                           object of type Event_Dispatcher2
     *
     * @return object $this
     */
    public function addNestedDispatcher(Event_Dispatcher2 $dispatcher)
    {
        $name = $dispatcher->getName();
        $this->nestedDispatchers[$name] = $dispatcher;

        return $this;
    }


    /**
     * Remove a nested dispatcher
     *
     * @param mixed $dispatcher Event_Dispatcher2 object | class name Dispatcher to remove
     *
     * @return   object $this
     */
    public function removeNestedDispatcher($dispatcher)
    {
        if (is_object($dispatcher)) {
            $dispatcher = $dispatcher->getName();
        }
        if (isset($this->nestedDispatchers[$dispatcher])) {

            unset($this->nestedDispatchers[$dispatcher]);
        }

        return $this;
    }


    /**
     * Changes the class used for notifications
     *
     * You may call this method on an object to change it for a single
     * dispatcher
     *
     * @param string $class Name of the notification class
     *
     * @return object $this
     */
    public final function setNotificationClass($class)
    {
        if (!isset($this->notificationClass)) {
            throw new Event_Dispatcher_User_Exception('The method setNotificationClass can ONLY be called on Event_Dispatcher object. It cannot be called on any child objects!');
        }

        $this->notificationClass = $class;
         
        return $this;
    }

}

?>