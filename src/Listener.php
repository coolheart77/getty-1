<?php

namespace Stahlstift\Getty;

interface Listener
{

    /**
     * @param int $id
     * @param string $url
     * @param array $httpStreamOptions
     */
    public function requestStart($id, $url, array $httpStreamOptions);

    /**
     * @param int $id
     */
    public function requestEnd($id);

}
