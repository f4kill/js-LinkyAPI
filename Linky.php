<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 KiboOst
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace Linky;

/**
 * Main class for Linky data retrieval
 */
class Linky
{
    protected $version = '0.12';

    /** @var EnedisCredentials */
    protected $credentials = null;

    /** @var bool */
    protected $isAuthenticated = false;

    protected $_cookFile = '';

    /** @var string */
    protected $_loginBaseUrl = 'https://espace-client-connexion.enedis.fr';
    /** @var string */
    protected $_APIBaseUrl = 'https://espace-client-particuliers.enedis.fr/group/espace-particuliers';
    /** @var string */
    protected $_APILoginUrl = '/auth/UI/Login';
    /** @var string */
    protected $_APIHomeUrl = '/home';
    /** @var string */
    protected $_APIDataUrl = '/suivi-de-consommation';

    protected $requestHandler = null;
    /** @var bool */
    protected $withUnits;

    /**
     * Linky constructor.
     *
     * @param EnedisCredentials $credentials
     * @param bool              $withUnits   Pass true to get values with their unit (ie. kW), otherwise raw values are provided
     *
     * @throws LinkyException
     */
    public function __construct(EnedisCredentials $credentials, bool $withUnits = false)
    {
        $this->credentials = $credentials;
        $this->withUnits = $withUnits;

        $this->auth();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Authentication with Enedis
     *
     * @return bool
     * @throws LinkyException
     */
    protected function auth(): bool
    {
        if ($this->credentials === null) {
            return false;
        }

        $postdata = array(
            'IDToken1' => $this->credentials->getUsername(),
            'IDToken2' => $this->credentials->getPassword(),
            'SunQueryParamsString' => base64_encode('realm=particuliers'),
            'encoded' => 'true',
            'gx_charset' => 'UTF-8',
        );

        $url = $this->_loginBaseUrl.$this->_APILoginUrl;
        $response = $this->request('POST', $url, $postdata);

        // Checking status
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        if (!array_key_exists('iPlanetDirectoryPro', $cookies)) {
            throw new LinkyException('Could not connect. Check your credentials.');
        }

        $this->isAuthenticated = true;

        $url = 'https://espace-client-particuliers.enedis.fr/group/espace-particuliers/accueil';
        $response = $this->request('GET', $url);

        return true;
    }

    public function getHourlyData(\DateTime $date)
    {
        // From date - 2 days to date + 1 day...
        $startDate = clone $date;
        $startDate->sub(new \DateInterval('P2D'));
        $endDate = clone $date;
        $endDate->add(new \DateInterval('P1D'));

        $result = $this->requestData('urlCdcHeure', $startDate, $endDate);

        if (!isset($result['graphe']['data'])) {
            return null;
        }

        $output = array();
        $currentTime = new \DateTime('23:30');

        $data = $result['graphe']['data'];
        $end = count($data);
        for ($i = $end - 1; $i >= $end - 48; $i--) {
            $value = $data[$i]['valeur'];

            $output[$currentTime->format('Y-m-d H:i')] = $this->formatValue($value);

            $currentTime->modify('-30 min');
        }

        $output = array_reverse($output);

        return $output;
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getDailyData(\DateTime $startDate, \DateTime $endDate)
    {
        // Checking the maximum allowed days
        $nbDays = ($endDate->getTimestamp() - $startDate->getTimestamp()) / 86400;
        if ($nbDays > 31) {
            throw new LinkyException('Number of days cannot exceed 31 days');
        }

        $result = $this->requestData('urlCdcJour', $startDate, $endDate);

        if (!isset($result['graphe']['data'])) {
            return null;
        }

        $output = array();

        $data = $result['graphe']['data'];
        $currentDate = clone $startDate;
        foreach ($data as $day) {
            $value = $day['valeur'];

            $output[$currentDate->format('Y-m-d')] = $this->formatValue($value);

            $currentDate->modify('+1 day');
        }

        return $output;
    }

    public function getMonthlyData(\DateTime $startDate, \DateTime $endDate)
    {
        $result = $this->requestData('urlCdcMois', $startDate, $endDate);

        if (!isset($result['graphe']['data'])) {
            return null;
        }

        $output = array();

        $data = $result['graphe']['data'];
        $currentMonth = clone $startDate;
        foreach ($data as $month) {
            $value = $month['valeur'];

            $output[$currentMonth->format('Y-m')] = $this->formatValue($value);

            $currentMonth->modify('+1 month');
        }

        return $output;
    }

    public function getYearlyData()
    {
        $result = $this->requestData('urlCdcAn', null, null);

        if (!isset($result['graphe']['data'])) {
            return null;
        }

        $currentYear = new \DateTime();
        $output = array();

        $data = $result['graphe']['data'];
        $c = count($data) - 1;
        $currentYear->modify('- '.$c.' year');
        foreach ($data as $year) {
            $value = $year['valeur'];

            $output[$currentYear->format('Y')] = $this->formatValue($value);

            $currentYear->modify('+1 year');
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAll(): array
    {
        $data = array(
            'hours' => null,
            'days' => null,
            'months' => null,
            'years' => null,
        );

        $timezone = 'Europe/Paris';
        $today = new \DateTime('NOW', new \DateTimeZone($timezone));
        $yesterday = clone $today;
        $yesterday->sub(new \DateInterval('P1D')); // Enedis' last data are for yesterday

        // Geting hour data for yesterday
        $data['hours'] = $this->getHourlyData($yesterday);

        // Getting daily data for the last 30 days
        $monthAgo = clone $yesterday;
        $monthAgo->sub(new \DateInterval('P30D'));
        $data['days'] = $this->getDailyData($monthAgo, $today);

        // Getting monthly data for the last 365 days
        $yearAgo = clone $yesterday;
        $yearAgo->sub(new \DateInterval('P1Y'));
        $yearAgo->setDate($yearAgo->format('Y'), $yearAgo->format('m'), '01');
        $data['months'] = $this->getMonthlyData($yearAgo, $yesterday);

        // Getting yearly data
        $data['years'] = $this->getYearlyData();

        return $data;
    }

    /**
     * @param string         $resourceId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     *
     * @return mixed
     * @throws LinkyException
     */
    protected function requestData(string $resourceId, ?\DateTime $startDate, ?\DateTime $endDate)
    {
        $p_p_id = 'lincspartdisplaycdc_WAR_lincspartcdcportlet';

        $url = $this->_APIBaseUrl.$this->_APIDataUrl;
        $url .= '?p_p_id='.$p_p_id;
        $url .= '&p_p_lifecycle=2';
        $url .= '&p_p_mode=view';
        $url .= '&p_p_resource_id='.$resourceId;
        $url .= '&p_p_cacheability=cacheLevelPage';
        $url .= '&p_p_col_id=column-1';
        $url .= '&p_p_col_count=2';

        $postdata = null;
        if ($startDate !== null) {
            $postdata = array(
                '_'.$p_p_id.'_dateDebut' => $startDate->format('d/m/Y'),
                '_'.$p_p_id.'_dateFin' => $endDate->format('d/m/Y'),
            );
        }

        $response = $this->request('GET', $url, $postdata);
        $jsonArray = json_decode($response, true);

        if ($jsonArray['etat']['valeur'] == 'erreur') {
            throw new LinkyException('Enedis returned an error');
        } else if ($jsonArray['etat']['valeur'] == 'nonActive') {
            throw new LinkyException('Enedis returned a "nonActive" answer: no data retrieved');
        }

        // Remove useless data at the beginning and end of data array when there is an offset
        if (isset($jsonArray['graphe']['decalage'])) {
            $decalage = $jsonArray['graphe']['decalage'];

            while ($decalage > 0) {
                array_shift($jsonArray['graphe']['data']);
                array_pop($jsonArray['graphe']['data']);
                $decalage--;
            }
        }

        return $jsonArray;
    }

    /**
     * Support function handling all get/post request with curl
     *
     * @param string     $method
     * @param string     $url
     * @param array|null $postdata
     *
     * @return string|bool
     * @throws LinkyException
     */
    protected function request(string $method, string $url, ?array $postdata = null)
    {
        if (!isset($this->requestHandler)) {
            $this->setupRequestHandler();
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);
        //echo 'url: ', $url, "<br>";
        curl_setopt($this->requestHandler, CURLOPT_URL, $url);

        if ($method === 'POST') {
            curl_setopt($this->requestHandler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->requestHandler, CURLOPT_POST, true);
        } else {
            curl_setopt($this->requestHandler, CURLOPT_POST, false);
        }

        if (isset($postdata)) {
            curl_setopt($this->requestHandler, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->requestHandler, CURLOPT_POSTFIELDS, http_build_query($postdata));
        }

        $response = curl_exec($this->requestHandler);

        //$info   = curl_getinfo($this->_curlHdl);
        //echo "<pre>cURL info".json_encode($info, JSON_PRETTY_PRINT)."</pre><br>";

        if ($response === false) {
            throw new LinkyException(curl_error($this->requestHandler));
        }

        if ($this->isAuthenticated) {
            $header_size = curl_getinfo($this->requestHandler, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $response = substr($response, $header_size);
        }

        return $response;
    }

    /**
     * Sets up the curl request handler
     */
    protected function setupRequestHandler(): void
    {
        $this->requestHandler = curl_init();

        curl_setopt($this->requestHandler, CURLOPT_COOKIEJAR, $this->_cookFile);
        curl_setopt($this->requestHandler, CURLOPT_COOKIEFILE, $this->_cookFile);

        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($this->requestHandler, CURLOPT_HEADER, true);
        curl_setopt($this->requestHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->requestHandler, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($this->requestHandler, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0');
    }

    /**
     * @param float|null $value
     *
     * @return string|null
     */
    protected function formatValue(?float $value)
    {
        if ($value === null || $value < 0) {
            return null;
        }

        if ($this->withUnits) {
            $value .= 'kWh';
        }

        return $value;
    }
}
