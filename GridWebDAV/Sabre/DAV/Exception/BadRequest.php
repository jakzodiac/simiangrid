<?php

/**
 * BadRequest 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: BadRequest.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * BadRequest
 *
 * The BadRequest is thrown when the user submitted an invalid HTTP request
 */
class Sabre_DAV_Exception_BadRequest extends Sabre_DAV_Exception {

    function getHTTPCode() {

        return 400; 

    }

}