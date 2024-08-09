<?php

namespace Kanboard\Plugin\Mailmagik\Model;

use Kanboard\Core\Base;

/**
 * Class Kanboard\Plugin\Mailmagik\Model;
 *
 * @author Craig Crosby <creecros@gmail.com>
 */
class MyMetadataTypeModel extends Base
{
    /**
     * SQL table name for MetadataType.
     *
     * @var string
     */
    const TABLE = 'metadata_types';
    
    public function isTypeDate(string $name) : bool
    {
        $row = $this->db->table(self::TABLE)
            ->eq('human_name', $name)
            ->findOne();
        return $row != null ? $row['data_type'] == 'date' : false;
    }
    
}
