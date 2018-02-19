<?php

/* * *
 * For testing general Web services on various pages
 * @author Duy Dinh
 * @date 06/07/2016
 */

class WebPagesTest extends TestCase {
    /*
     * Test some Web pages before loading     
     */

    public function testWebPages() {
        $this->visit('/')
                ->see('LOD and e-participation');
    }

}
