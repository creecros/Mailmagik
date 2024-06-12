<?php

namespace Kanboard\Plugin\Mailmagik\Helper;

use Kanboard\Core\Base;

class ParsingHelper extends Base
{
    /**
     * Parse message for data to feed DB
     *
     * @return array
     */

     // https://onlinephp.io/c/f1721

    public function parseData($message, $start = '&@', $end = '@&')
    {
        $values = array();

        $pattern = sprintf(
                '/%s(.*?)%s/',
                preg_quote($start),
                preg_quote($end)
            );

        preg_match_all($pattern, $message, $matches);
        foreach ($matches[1] as $match) {
            $values[strtok($match, '=')] =
                str_replace('"',"",substr($match, strpos($match, "=") + 1));
        };

       return $values;
    }

    /**
     * Remove all task data specs from message
     *
     * @param string $message
     * @return string remaining message
     */
    public function removeTaskData(string $message) : string
    {
        return preg_replace('/[&$]@.*@[&$]/', '', $message);
    }

    /**
     * Check the validity of parsed data.
     *
     * @return array
     */
    public function verifyData(&$updates, &$task)
    {
        $allmeta = $this->helper->parsing->getAllMeta($task['id']);  // With prefix
        $project_id = $task['project_id'];
        $veto_keys = array();

        foreach ($updates as $key => &$value) {
            // Meta keys are already prefixed with KEY_PREFIX
            if (($key == 'owner_id' && !ctype_digit($value)) || ($key == 'creator_id' && !ctype_digit($value))) {
                $value = $this->getUserId($value);
                continue;
            }

            // Column:
            // column_id = <pos>|<name>

            if ($key == 'column_id' ) {
                if (ctype_digit($value)) {
                    // Try as position, range 1...n
                    $columns = $this->columnModel->getList($project_id);

                    if (array_key_exists($value, $columns)) {
                        $value = array_keys($columns)[$value - 1];
                    } else {
                        $veto_keys[] = $key;
                    }

                    continue;
                } else {
                    // Try as name
                    $column_id = $this->columnModel->getColumnIdByTitle($project_id, $value);

                    if ($column_id  > 0) {
                        $value = $column_id;
                    } else {
                        $veto_keys[] = $key;
                    }

                    continue;
                }

                $veto_keys[] = $key;
            } // column_id

            // Category
            // category_id = <name>

            if ($key == 'category_id' ) {
                $category_id = $this->categoryModel->getIdByName($project_id, $value);

                if ($category_id  > 0) {
                    $value = $category_id;
                } else {
                    $veto_keys[] = $key;
                }

                continue;
            } // category_id

            if (strpos($key, KEY_PREFIX) === 0 ) {
                if (!array_key_exists($key, $allmeta)) {
                    $veto_keys[] = $key;
                }
            } else {
                if (!array_key_exists($key, $task)) {
                    $veto_keys[] = $key;
                }
            }
        }
        unset($value);

        return array(
            empty($veto_keys),
            $veto_keys,
        );

    }

    public function parseAllData(string $message, $task_id) : array
    {
        $updates = array();

        $parsed_taskdata = $this->helper->parsing->parseData($message);
        $parsed_metadata = $this->helper->parsing->parseData($message, '$@', '@$');

        if ($this->configModel->get('mailmagik_parsing_remove_data', '1') == 1) {
            $updates['description'] = $this->helper->parsing->removeTaskData($message);
        } else {
            $updates['description'] = $message;
        }

        $prefixed_meta = array();
        foreach ($parsed_metadata as $key => $value) {
            $prefixed_meta[KEY_PREFIX . $key] = $value;
        }

        $updates = array_merge($updates, $parsed_taskdata, $prefixed_meta);
        $task = $this->taskFinderModel->getById($task_id);

        [$valid, $denied_keys] = $this->verifyData($updates, $task);
        $denied_keys =  str_replace(KEY_PREFIX, "", $denied_keys);

        return $valid ? $updates : array(
            'title' => " [Parsing Errors!]",
            'description' => $updates['description'] .= PHP_EOL . PHP_EOL .
                t('Parsing Errors: The following keys are either invalid or not allowed: ') . PHP_EOL . PHP_EOL .
                implode(', ', $denied_keys)
            );
    }

    /**
     * Get all relevant meta fields with its values.
     * The keys get prefixed.
     *
     * @return array
     */
    public function getAllMeta($task_id) : array
    {
        $values = array();
        $meta_fields = $this->taskMetadataModel->getAll($task_id);
        foreach ($meta_fields as $key => $value) {
            $values[KEY_PREFIX . $key ] = $value;
        }
        return $values;
    }

    /**
     * Get user_id from email.
     * @param string $email
     * @return int user_id | null
     */
    private function getUserId(string $email)
    {
        if (!$this->userModel->getByEmail($email)) {
            return null;
        } else {
            return $this->userModel->getByEmail($email)['id'];
        }
    }

}
