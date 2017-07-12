<?php
/**
 * File: TwigExt.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 8:23
 */
class TwigExt extends \Twig_Extension
{

    public function getFilters()
    {
        $filter = array(
            new Twig_SimpleFilter('id_encode', array($this, 'encodeHashFilter')),
        );
        return $filter;
    }

    public function encodeHashFilter($id)
    {
        return instanceHashId()->encode(func_get_args());
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'yc_ext';
    }
}