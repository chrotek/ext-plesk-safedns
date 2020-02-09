<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_SafeDNS_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        $buttons = [[
            'place'       => self::PLACE_DOMAIN,
            'title'       => 'SafeDNS',
            'description' => 'SafeDNS Plesk Integration',
            'icon'        => pm_Context::getBaseUrl().'images/icons/safedns-icon.png',
            // TODO Change below to the extension's page
            'link'        => pm_Settings::get('google.com'),
            'newWindow'   => true
        ]];

        return $buttons;
    }
}
