<?php
# *** LICENSE ***
# This file is a addon for BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2016 Timo Van Neerden.
#
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['addons'][] = array(
    'tag' => 'params_example',
    'name' => 'Params Example',
    'desc' => 'No real utility...',
    'version' => '1.0.0',
    'config' => array(
        'exemple_config_1' => array(
            'type' => 'bool',
            'label' => array(
                'en' => 'label for bool',
                'fr' => 'label pour bool'
            ),
            'value' => true,
        ),
        'exemple_config_2' => array(
            'type' => 'int',
            'label' => array(
                'en' => 'label for int',
                'fr' => 'label pour int'
            ),
            'value' => true,
            'value' => 10,
            'value_min' => 1,
            'value_max' => 20,
        ),
        'exemple_config_3' => array(
            'type' => 'text',
            'label' => array(
                'en' => 'label for text',
                'fr' => 'label pour text'
            ),
            'value' => 'There is an exemple.',
        ),
		'exemple_config_4' => array(
			'type' => 'select',
			'label' => array(
				'en' => 'label for select',
				'fr' => 'label pour select'
			),
			// default value must be a key in 'options'
			'value' => 'yes',
			'options' => array(
				// key => option lang
				'yes' => array(
							'fr' => 'Oui',
							'en' => 'yes',
						),
				'no' => array(
							'fr' => 'Non',
							'en' => 'No',
						),
				'option3' => array(
							'fr' => 'Peut-être',
							'en' => 'Maybe',
						),
			),
		),
    )
);

// returns HTML <table> calender
function addon_params_example()
{
    // get the addon conf
    $addon_conf = addon_get_conf('advanced_rss');

    // do your stuff...
}
