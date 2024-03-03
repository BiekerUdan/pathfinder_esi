<?php


namespace Exodus4D\ESI\Mapper\EveScout;

use Exodus4D\Pathfinder\Data\Mapper\AbstractIterator;

class Connection extends AbstractIterator {

    /**
     * @var array
     */
    protected static $map = [
        'id'                                => 'id',
        'signature_type'                    => 'type',
        'in_system_name'                    => 'name',
        'completed'                         => ['state' => 'name'],
        'updated_at'                        => ['state' => 'updated'],

        //Thera || Turner
        'out_system_id'                     => ['source' => 'id'],
        'out_system_name'                   => ['source' => 'name'],
        'out_signature'                     => ['sourceSignature' => 'name'],

        //Connection
        'in_system_id'                      => ['target' => 'id'],
        'in_system_name'                    => ['target' => 'name'],
        'in_signature'                      => ['targetSignature' => 'name'],

        
        'expires_at'                        => ['wormhole' => 'estimatedEol'],

        'created_at'                        => 'created',
        'updated_at'                        => 'updated',

        'created_by_id'                     => ['character' => 'id'],
        'created_by_name'                   => ['character' => 'name']
    ];
}