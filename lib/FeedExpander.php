<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    private array $feed;

    public function collectExpandableDatas(string $url, $maxItems = -1, $headers = [])
    {
        if (!$url) {
            throw new \Exception('There is no $url for this RSS expander');
        }
        $maxItems = (int) $maxItems;
        if ($maxItems === -1) {
            $maxItems = 999;
        }
        $accept = [MrssFormat::MIME_TYPE, AtomFormat::MIME_TYPE, '*/*'];
        $httpHeaders = array_merge(['Accept: ' . implode(', ', $accept)], $headers);
        $xmlString = getContents($url, $httpHeaders);
        if ($xmlString === '') {
            throw new \Exception(sprintf('Unable to parse xml from `%s` because we got the empty string', $url), 10);
        }
        $xmlString = $this->prepareXml($xmlString);
        $feedParser = new FeedParser();
        try {
            $this->feed = $feedParser->parseFeed($xmlString);
        } catch (\Exception $e) {
            // FeedMergeBridge relies on this string
            throw new \Exception(sprintf('Failed to parse xml from %s: %s', $url, create_sane_exception_message($e)));
        }

        $items = array_slice($this->feed['items'], 0, $maxItems);
        // todo: extract parse logic out from FeedParser
        foreach ($items as $item) {
            // Give bridges a chance to modify the item
            $item = $this->parseItem($item);
            if ($item) {
                $this->items[] = $item;
            }
        }
    }

    /**
     * This method is overridden by bridges
     *
     * @return array
     */
    protected function parseItem(array $item)
    {
        return $item;
    }

    /**
    * Prepare XML document to make it more acceptable by the parser
    * This method can be overriden by bridges to change this behavior
    *
    * @return string
    */
    protected function prepareXml(string $xmlString): string
    {
        // Remove problematic escape sequences
        $problematicStrings = [
            '&nbsp;',
            '&raquo;',
            '&rsquo;',
        ];
        return str_replace($problematicStrings, '', $xmlString);
    }

    public function getURI()
    {
        return $this->feed['uri'] ?? parent::getURI();
    }

    public function getName()
    {
        return $this->feed['title'] ?? parent::getName();
    }

    public function getIcon()
    {
        return $this->feed['icon'] ?? parent::getIcon();
    }
}
