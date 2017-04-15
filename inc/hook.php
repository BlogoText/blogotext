<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***


/**
 * BASED ON :
 *
 * DS hook - Dirty Script, a light hook system
 * @licence   MIT
 * @version   0.03.002 beta
 * @link      https://github.com/DirtyScript/hook
 */


/**
 * hook system
 * push a new hook
 *
 * @param string $hook_name, the hook name
 * @param string $function, the function to call
 * @param int $priority, in case of you have multiple callback function
 *                       who need to run in specific order (1 < 10)
 */
function hook_push($hook_name, $function, $priority = 10)
{
    // prevent hook on admin side
    if (defined('IS_IN_ADMIN')) {
        return true;
    }

    if (!isset($GLOBALS['hooks']) || !is_array($GLOBALS['hooks'])) {
        $GLOBALS['hooks'] = array();
    }
    $GLOBALS['hooks'][$hook_name][$priority][] = $function;
}

/**
 * the hook trigger
 * call the functions who have a call pushed
 *
 * @param string $hook_name, the hook name, required
 * @param mixed ... , you can push all the params you want
 * @return array, the returns of the functions to call
 */
function hook_trigger($hook_name)
{
    $args = func_get_args();

    // prevent hook on admin side
    if (defined('IS_IN_ADMIN')) {
        return $args;
    }

    if (!isset($GLOBALS['hooks']) || !is_array($GLOBALS['hooks'])) {
        return $args;
    }
    if (!isset($GLOBALS['hooks'][$hook_name]) || !is_array($GLOBALS['hooks'][$hook_name]) || count($GLOBALS['hooks'][$hook_name]) < 1) {
        return $args;
    }

    foreach ($GLOBALS['hooks'][$hook_name] as $functions) {
        // sort by priority
        krsort($functions);
        foreach ($functions as $function) {
            if (function_exists($function)) {
                $args = call_user_func($function, $args);
            }
        }
    }

    return $args;
}

/**
 * check if the return of a hook seem's valid
 *
 * @param string $hook_name
 * @param int    $args_count the total count of hook_trigger() args
 * @param array  $args       the var returned by hook_trigger()
 * @param bool   $must_die   if true, use DIE(), else return bool
 * @return bool||die()
 */
function hook_check($hook_name, $args_count, $args, $must_die = true)
{
    // prevent hook on admin side
    if (defined('IS_IN_ADMIN')) {
        return true;
    }

    if (!is_array($args)) {
        if ($must_die) {
            die('hook : '. $hook_name .', must return an array.');
        } else {
            return false;
        }
    }

    if ($args_count !== count($args)) {
        if ($must_die) {
            die('hook : '. $hook_name .', does not return the correct number of arguments.');
        } else {
            return false;
        }
    }

    if (!isset($args['0']) || $args['0'] != $hook_name) {
        if ($must_die) {
            die('hook : '. $hook_name .', the first element of the array must be the name of the hook.');
        } else {
            return false;
        }
    }

    // check the args key
    while (--$args_count) {
        if (!isset($args[$args_count])) {
            if ($must_die) {
                die('hook : '. $hook_name .', missing $args['.$args_count.'] .');
            } else {
                return false;
            }
        }
    }

    return true;
}

/**
 * Shortcut for hook_trigger() -> hook_check()
 *
 * if return false, that means tests have fail
 *
 * @param string $hook_name, the hook name, required
 * @param mixed ... , you can push all the params you want
 * @return false|array, the returns of the functions to call
 */
function hook_trigger_and_check($hook_name)
{
    $args = func_get_args();

    // prevent hook on admin side
    if (defined('IS_IN_ADMIN')) {
        return $args;
    }

    $tmp_hook = call_user_func_array('hook_trigger', $args);
    if (hook_check($hook_name, func_num_args(), $tmp_hook)) {
        return $tmp_hook;
    }

    return false;
}
