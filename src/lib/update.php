<?php

class Update extends Util_Abstract
{

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        parent::__construct($console_instance);
    }

    /**
     * Check for an update, and parse out all relevant information if one exists
     * @param $auto Whether this is an automatic check or triggered intentionally
     * @return Boolean True if newer version exists. False if:
     *  - no new version or
     *  - if auto, but auto check is disabled or
     *  - if auto, but not yet time to check or
     *  - if update is disabled
     */
    public function updateCheck($auto=true, $output=false)
    {
        $this->log("Running update check");

        if (empty($this->update_version_url))
        {
            if (($output and !$auto) or $this->verbose) $this->output("Update is disabled - update_version_url is empty");
            return false; // update disabled
        }

        if (is_null($this->update_exists))
        {
            $now = time();

            // If this is an automatic check, make sure it's time to check again
            if ($auto)
            {
                $this->log("Designated as auto-update");

                // If disabled, return false
                if ($this->update_auto <= 0)
                {
                    $this->log("Auto-update is disabled - update_auto <= 0");
                    return false; // auto-update disabled
                }

                // If we haven't checked before, we'll check now
                // Otherwise...
                if (!empty($this->update_last_check))
                {
                    $last_check = strtotime($this->update_last_check);

                    // Make sure last check was a valid time
                    if (empty($last_check) or $last_check < 0)
                    {
                        $this->error('Issue with update_last_check value (' . $this->update_last_check . ')');
                    }

                    // Has it been long enough? If not, we'll return false
                    $seconds_since_last_check = $now - $last_check;
                    if ($seconds_since_last_check < $this->update_auto)
                    {
                        $this->log("Only $seconds_since_last_check seconds since last check.  Configured auto-update is " . $this->update_auto . " seconds");
                        return false; // not yet time to check
                    }
                }
            }

            // curl, get contents at config url
            $curl = $this->getCurl($this->update_version_url, true);
            $update_contents = $this->execCurl($curl);

            // look for version match
            if ($this->update_version_pattern[0] === true)
            {
                $this->update_version_pattern[0] = $this->update_pattern_standard;
            }
            if (!preg_match($this->update_version_pattern[0], $update_contents, $match))
            {
                $this->log($update_contents);
                $this->log($this->update_version_pattern[0]);
                $this->error('Issue with update version check - pattern not found at ' . $this->update_version_url);
            }
            $index = $this->update_version_pattern[1];
            $this->update_version = $match[$index];

            // check if remote version is newer than installed
            $class = get_called_class();
            $this->update_exists = version_compare($class::VERSION, $this->update_version, '<');

            if ($output or $this->verbose)
            {
                if ($this->update_exists)
                {
                    $this->hr('>');
                    $this->output("An update is available: version " . $this->update_version . " (currently installed version is " . $class::VERSION . ")");
                    if ($this->method != 'update')
                    {
                        $this->output(" - Run 'update' to install latest version.");
                        $this->output(" - See 'help update' for more information.");
                    }
                    $this->hr('>');
                }
                else
                {
                    $this->output("Already at latest version (" . $class::VERSION . ")");
                }
            }

            // look for download match
            if ($this->update_download_pattern[0] === true)
            {
                $this->update_download_pattern[0] = $this->update_pattern_standard;
            }
            if (!preg_match($this->update_download_pattern[0], $update_contents, $match))
            {
                $this->error('Issue with update download check - pattern not found at ' . $this->update_version_url);
            }
            $index = $this->update_download_pattern[1];
            $this->update_url = $match[$index];

            if ($this->update_check_hash)
            {
                // look for hash algorithm match
                if ($this->update_hash_algorithm_pattern[0] === true)
                {
                    $this->update_hash_algorithm_pattern[0] = $this->hash_pattern_standard;
                }
                if (!preg_match($this->update_hash_algorithm_pattern[0], $update_contents, $match))
                {
                    $this->error('Issue with update hash algorithm check - pattern not found at ' . $this->update_version_url);
                }
                $index = $this->update_hash_algorithm_pattern[1];
                $this->update_hash_algorithm = $match[$index];

                // look for hash match
                if ($this->update_hash_pattern[0] === true)
                {
                    $this->update_hash_pattern[0] = $this->hash_pattern_standard;
                }
                if (!preg_match($this->update_hash_pattern[0], $update_contents, $match))
                {
                    $this->error('Issue with update hash check - pattern not found at ' . $this->update_version_url);
                }
                $index = $this->update_hash_pattern[1];
                $this->update_hash = $match[$index];
            }

            $this->configure('update_last_check', gmdate('Y-m-d H:i:s T', $now), true);
            $this->saveConfig();
        }

        $this->log(" -- update_exists: " . $this->update_exists);
        $this->log(" -- update_version: " . $this->update_version);
        $this->log(" -- update_url: " . $this->update_url);
        $this->log(" -- update_hash_algorithm: " . $this->update_hash_algorithm);
        $this->log(" -- update_hash: " . $this->update_hash);

        return $this->update_exists;
    }

}
Command_Abstract::$util_classes[]="Update";
