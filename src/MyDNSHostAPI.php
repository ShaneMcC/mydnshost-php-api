<?php

	/**
	 * Class to interact with mydnshost api.
	 */
	class MyDNSHostAPI {
		/** Base URL. */
		private $baseurl = 'https://api.mydnshost.co.uk/';
		/** API Version. */
		private $version = '1.0';
		/** Auth Data. */
		private $auth = FALSE;
		/** Are we impersonating someone? */
		private $impersonate = FALSE;
		/** Are we impersonating an email address or an ID? */
		private $impersonateType = FALSE;
		/** Are we accessing domain functions with admin override? */
		private $domainAdmin = FALSE;
		/** Debug mode value. */
		private $debug = FALSE;
		/** Last API Response */
		private $lastResponse = NULL;

		/**
		 * Create a new MyDNSHostAPI
		 *
		 * @param $baseurl Base URL to connect to.
		 */
		public function __construct($baseurl) {
			$this->baseurl = $baseurl;
		}

		/**
		 * Enable/disable debug mode
		 *
		 * @param $value New debug value
		 * @return $this for chaining.
		 */
		public function setDebug($value) {
			$this->debug = $value;
			return $this;
		}

		/**
		 * Are we in debug mode?
		 *
		 * @return True/False if in debugging mode.
		 */
		public function isDebug() {
			return $this->debug;
		}

		/**
		 * Auth using a username and password.
		 *
		 * @param $user User to auth with
		 * @param $pass Password to auth with
		 * @param $key (Optional) 2FA Key for login.
		 * @return $this for chaining.
		 */
		public function setAuthUserPass($user, $pass, $key = NULL) {
			$this->auth = ['type' => 'userpass', 'user' => $user, 'pass' => $pass, '2fa' => $key];
			return $this;
		}

		/**
		 * Auth using a username and api key.
		 *
		 * @param $user User to auth with
		 * @param $key Key to auth with
		 * @return $this for chaining.
		 */
		public function setAuthUserKey($user, $key) {
			$this->auth = ['type' => 'userkey', 'user' => $user, 'key' => $key];
			return $this;
		}

		/**
		 * Auth using a domain and domain key.
		 *
		 * @param $domain Domain to auth with
		 * @param $key Key to auth with
		 * @return $this for chaining.
		 */
		public function setAuthDomainKey($domain, $key) {
			$this->auth = ['type' => 'domainkey', 'domain' => $domain, 'key' => $key];
			return $this;
		}

		/**
		 * Auth using a session ID.
		 *
		 * @param $sessionid ID to auth with
		 * @return $this for chaining.
		 */
		public function setAuthSession($sessionid) {
			$this->auth = ['type' => 'session', 'sessionid' => $sessionid];
			return $this;
		}

		/**
		 * Auth using a custom auth method.
		 *
		 * @param $auth Auth data.
		 * @return $this for chaining.
		 */
		public function setAuth($auth) {
			$this->auth = $auth;
			return $this;
		}

		/**
		 * Impersonate a user
		 *
		 * @param $user User to impersonate
		 * @param $type (Default: email) Is $user an email or id?
		 * @return $this for chaining.
		 */
		public function impersonate($user, $type = 'email') {
			$this->impersonate = $user;
			$this->impersonateType = $type;

			return $this;
		}

		/**
		 * Ping the API
		 *
		 * @param $time [Optional] Time to send as extra param.
		 * @return Data from API
		 */
		public function ping($time = NULL) {
			$result = $time === NULL ? $this->api('/ping', 'GET') : $this->api('/ping/' . $time, 'GET');
			return isset($result['response']) ? $result['response'] : NULL;
		}

		/**
		 * Get version info from the API.
		 *
		 * @return Array of user data or null if we are not authed.
		 */
		public function getVersion() {
			$result = $this->api('/version');
			return isset($result['response']) ? $result['response'] : NULL;
		}

		/**
		 * Register a new account.
		 *
		 * @param $email Email address
		 * @param $name Real namereturn isset($result['response']) ? $result['response'] : NULL;/sessi
		 * @return Data from API
		 */
		public function register($email, $name) {
			return $this->api('/register', 'POST', ['email' => $email, 'realname' => $name]);
		}

		/**
		 * Confirm account registration
		 *
		 * @param $user User ID
		 * @param $code Verify Code
		 * @param $password Requested Password
		 * @return Data from API
		 */
		public function registerConfirm($user, $code, $password) {
			return $this->api('/register/confirm/' . $user, 'POST', ['code' => $code, 'password' => $password]);
		}

		/**
		 * Resend welcome email.
		 *
		 * @param $user User ID
		 * @return Data from API
		 */
		public function resendWelcome($userid) {
			return $this->api('/users/' . $userid . '/resendwelcome', 'POST', []);
		}

		/**
		 * Submit a password reset request
		 *
		 * @param $email Email address
		 * @return Data from API
		 */
		public function forgotpassword($email) {
			return $this->api('/forgotpassword', 'POST', ['email' => $email]);
		}

		/**
		 * Confirm a password reset request
		 *
		 * @param $user User ID
		 * @param $code Verify Code
		 * @param $password Requested Password
		 * @return Data from API
		 */
		public function forgotpasswordConfirm($user, $code, $password) {
			return $this->api('/forgotpassword/confirm/' . $user, 'POST', ['code' => $code, 'password' => $password]);
		}

		/**
		 * Check if we have valid auth details.
		 *
		 * @return True if we can auth successfully.
		 */
		public function validAuth() {
			if ($this->auth === FALSE) {
				return FALSE;
			}

			return $this->getUserData() !== NULL;
		}

		/**
		 * Get information about the user we are authed as and our current
		 * access level.
		 *
		 * @return Array of user data or null if we are not authed.
		 */
		public function getUserData() {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/userdata');
			return isset($result['response']) ? $result['response'] : NULL;
		}

		/**
		 * Get information about all users we can see.
		 *
		 * @return Result from the API.
		 */
		public function getUsers() {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/users');
			return $result;
		}

		/**
		 * Set information a given user id.
		 *
		 * @param $userid User ID to get data for (Default: 'self')
		 * @return Result from the api
		 */
		public function getUserInfo($userID = 'self') {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/users/' . $userID);
			return isset($result['response']) ? $result['response'] : NULL;
		}

		/**
		 * Update information about the user we are authed as
		 *
		 * @param $data Data to use for the update
		 * @param $userid User ID to edit (Default: 'self')
		 * @return Result from the api
		 */
		public function setUserInfo($data, $userID = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userID, 'POST', $data);
		}

		/**
		 * Create a new user
		 *
		 * @param $data for the create operation
		 * @return Result from the api
		 */
		public function createUser($data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/create', 'POST', $data);
		}

		/**
		 * Delete the given user id.
		 *
		 * @param $userid User ID to delete.
		 * @return Result from the api
		 */
		public function deleteUser($userID) {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userID, 'DELETE');
		}

		/**
		 * Get API Keys for a user
		 *
		 * @param  $userid User ID to get keys for
		 * @return Array of api keys.
		 */
		public function getAPIKeys($userid = 'self') {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/users/' . $userid . '/keys');
			return isset($result['response']) ? $result['response'] : (isset($result['error']) ? NULL : []);
		}

		/**
		 * Create a new API Key.
		 *
		 * @param $data Data to use for the create
		 * @param $userid User ID to create key for
		 * @return Result of create operation.
		 */
		public function createAPIKey($data, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/keys', 'POST', $data);
		}

		/**
		 * Update an API Key.
		 *
		 * @param $key Key to update
		 * @param $data Data to use for the update
		 * @param $userid User ID to update key for
		 * @return Result of update operation.
		 */
		public function updateAPIKey($key, $data, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/keys/' . $key, 'POST', $data);
		}

		/**
		 * Delete a new API Key.
		 *
		 * @param $key Key to delete
		 * @param $userid User ID to delete key for
		 * @return Result of delete operation.
		 */
		public function deleteAPIKey($key, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/keys/' . $key, 'DELETE');
		}

		/**
		 * Get 2FA Keys for the current user
		 *
		 * @param $userid User ID to get keys for
		 * @return Array of 2FA keys.
		 */
		public function get2FAKeys($userid = 'self') {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/users/' . $userid . '/2fa');
			return isset($result['response']) ? $result['response'] : (isset($result['error']) ? NULL : []);
		}

		/**
		 * Create a new 2FA Key.
		 *
		 * @param $data Data to use for the create
		 * @param $userid User ID to create key for
		 * @return Result of create operation.
		 */
		public function create2FAKey($data, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/2fa', 'POST', $data);
		}

		/**
		 * Update a 2FA Key.
		 *
		 * @param $key Key to update
		 * @param $data Data to use for the update
		 * @param $userid User ID to update key for
		 * @return Result of update operation.
		 */
		public function update2FAKey($key, $data, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/2fa/' . $key, 'POST', $data);
		}

		/**
		 * Verify a 2FA Key.
		 *
		 * @param $key Key to verify
		 * @param $code Code to verify with
		 * @param $userid User ID to verify key for
		 * @return Result of update operation.
		 */
		public function verify2FAKey($key, $code, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/2fa/' . $key . '/verify', 'POST', ['code' => $code]);
		}

		/**
		 * Delete a 2FA Key.
		 *
		 * @param $key Key to delete
		 * @param $userid User ID to delete key for
		 * @return Result of delete operation.
		 */
		public function delete2FAKey($key, $userid = 'self') {
			if ($this->auth === FALSE) { return []; }

			return $this->api('/users/' . $userid . '/2fa/' . $key, 'DELETE');
		}

		/**
		 * Get a session ID from the backend
		 *
		 * @return Backend session ID or null if we are not authed.
		 */
		public function getSessionID() {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/session');
			return isset($result['response']['session']) ? $result['response']['session'] : NULL;
		}

		/**
		 * Delete our session
		 *
		 * @return Result of delete operation.
		 */
		public function deleteSession() {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api('/session', 'DELETE');
			return isset($result['response']['session']) ? $result['response']['session'] : NULL;
		}

		/**
		 * Enable or disable domain admin-override.
		 *
		 * @param $value (Default: true) Set value for domain admin override.
		 */
		public function domainAdmin($value = true) {
			$this->domainAdmin = true;

			return $this;
		}

		/**
		 * Get list of our domains.
		 *
		 * @return Array of domains or an empty array.
		 */
		public function getDomains() {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains');
			return isset($result['response']) ? $result['response'] : [];
		}

		/**
		 * Create a domain.
		 *
		 * @param $domain Domain to create.
		 * @param $owner (Default: NULL) Who to set as owner (if null, self);
		 * @return Result from the API
		 */
		public function createDomain($domain, $owner = NULL) {
			if ($this->auth === FALSE) { return []; }

			$data = ['domain' => $domain];
			if ($owner !== null) {
				$data['owner'] = $owner;
			}

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains', 'POST', $data);
		}

		/**
		 * Delete a domain.
		 *
		 * @param $domain Domain to delete.
		 * @return Result from the API
		 */
		public function deleteDomain($domain) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain, 'DELETE');
		}

		/**
		 * Get domain data for a given domain.
		 *
		 * @param $domain Domain to get data for
		 * @return Array of domains or an empty array.
		 */
		public function getDomainData($domain) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain);
			return isset($result['response']) ? $result['response'] : NULL;
		}

		/**
		 * Set domain data for a given domain.
		 *
		 * @param $domain Domain to set data for
		 * @param $data Data to set
		 * @return Result from the API
		 */
		public function setDomainData($domain, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain, 'POST', $data);
		}

		/**
		 * Get domain access for a given domain.
		 *
		 * @param $domain Domain to get access-data for
		 * @return Array of access info or an empty array.
		 */
		public function getDomainAccess($domain) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/access');
			return isset($result['response']['access']) ? $result['response']['access'] : [];
		}

		/**
		 * Set domain access for a given domain.
		 *
		 * @param $domain Domain to set access-data for
		 * @param $data New access data
		 * @return Response from the API
		 */
		public function setDomainAccess($domain, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/access', 'POST', $data);
		}

		/**
		 * Attempt to sync the domain to the backends.
		 *
		 * @param $domain Domain to export.
		 * @return Array of records or an empty array.
		 */
		public function syncDomain($domain) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/sync');
		}

		/**
		 * Export domain as bind zone file.
		 *
		 * @param $domain Domain to export.
		 * @return Array of records or an empty array.
		 */
		public function exportZone($domain) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/export');
			return isset($result['response']['zone']) ? $result['response']['zone'] : [];
		}

		/**
		 * Import domain from bind zone file.
		 *
		 * @param $domain Domain to import.
		 * @param $zone Zonefile data
		 * @return Array of records or an empty array.
		 */
		public function importZone($domain, $zone) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/import', 'POST', ['zone' => $zone]);
		}


		/**
		 * Get domain records for a given domain.
		 *
		 * @param $domain Domain to get records for
		 * @return Array of records or an empty array.
		 */
		public function getDomainRecords($domain) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records');
			return isset($result['response']['records']) ? $result['response']['records'] : [];
		}

		/**
		 * Get domain record for a given domain by id
		 *
		 * @param $domain Domain to get record for
		 * @param $id Record ID to get
		 * @return Array of records or an empty array.
		 */
		public function getDomainRecord($domain, $id) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records/' . $id);
			return isset($result['response']) ? $result['response'] : [];
		}

		/**
		 * Get domain records for a given domain filtered by name
		 *
		 * @param $domain Domain to get records for
		 * @param $name Record name to get
		 * @param $type (Optional) optional type to limit to
		 * @return Array of records or an empty array.
		 */
		public function getDomainRecordsByName($domain, $name, $type = null) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/record/' . $name . ($type != null ? '/' . $type : ''));
			return isset($result['response']['records']) ? $result['response']['records'] : [];
		}

		/**
		 * Set domain records for a given domain.
		 *
		 * @param $domain Domain to set records for
		 * @param $data Data to set
		 * @return Result from API
		 */
		public function setDomainRecords($domain, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records', 'POST', $data);
		}

		/**
		 * Set domain records for a given domain.
		 *
		 * @param $domain Domain to set record for
		 * @param $id Record ID to set
		 * @param $data Data to set
		 * @return Result from API
		 */
		public function setDomainRecord($domain, $id, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records/' . $id, 'POST', $data);
		}

		/**
		 * Delete records for a given domain.
		 *
		 * @param $domain Domain to delete records for
		 * @return Result from API
		 */
		public function deleteDomainRecords($domain) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records', 'DELETE');
			return $result['response'];
		}

		/**
		 * Delete a record for a given domain.
		 *
		 * @param $domain Domain to delete record for
		 * @param $id Record ID to delete
		 * @return Result from API
		 */
		public function deleteDomainRecord($domain, $id) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/records/' . $id, 'DELETE');
			return $result['response'];
		}

		/**
		 * Delete records for a given domain.
		 *
		 * @param $domain Domain to delete records for
		 * @param $name Record name to delete
		 * @param $type (Optional) optional type to limit delete to
		 * @return Result from API
		 */
		public function deleteDomainRecordsByName($domain, $name, $type = null) {
			if ($this->auth === FALSE) { return []; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/record/' . $name . ($type != null ? '/' . $type : ''), 'DELETE');
			return $result['response'];
		}

		/**
		 * Get Domain Keys for the given domain
		 *
		 * @param $domain Domain to get keys for
		 * @return Array of domain keys.
		 */
		public function getDomainKeys($domain) {
			if ($this->auth === FALSE) { return NULL; }

			$result = $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/keys');
			return isset($result['response']) ? $result['response'] : (isset($result['error']) ? NULL : []);
		}

		/**
		 * Create a new Domain Key.
		 *
		 * @param $domain Domain to create key for
		 * @param $data Data to use for the create
		 * @return Result of create operation.
		 */
		public function createDomainKey($domain, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/keys', 'POST', $data);
		}

		/**
		 * Update a Domain Key.
		 *
		 * @param $domain Domain to update key for
		 * @param $key Key to update
		 * @param $data Data to use for the update
		 * @return Result of update operation.
		 */
		public function updateDomainKey($domain, $key, $data) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/keys/' . $key, 'POST', $data);
		}

		/**
		 * Delete a Domain Key.
		 *
		 * @param $domain Domain to delete key for
		 * @param $key Key to delete
		 * @return Result of delete operation.
		 */
		public function deleteDomainKey($domain, $key) {
			if ($this->auth === FALSE) { return []; }

			return $this->api(($this->domainAdmin ? '/admin' : '') . '/domains/' . $domain . '/keys/' . $key, 'DELETE');
		}

		/**
		 * Get the last response from the API
		 *
		 * @return Last API Response.
		 */
		public function getLastResponse() {
			return $this->lastResponse;
		}

		/**
		 * Poke the API.
		 *
		 * @param $apimethod API Method to poke
		 * @param $method Request method to access the API with
		 * @param $data (Default: []) Data to send if POST
		 * @return Response from the API as an array.
		 */
		private function api($apimethod, $method = 'GET', $data = []) {
			$headers = [];
			$options = [];
			if ($this->auth !== FALSE) {
				if ($this->auth['type'] == 'session') {
					$headers['X-SESSION-ID'] = $this->auth['sessionid'];
				} else if ($this->auth['type'] == 'userkey') {
					$headers['X-API-USER'] = $this->auth['user'];
					$headers['X-API-KEY'] = $this->auth['key'];
				} else if ($this->auth['type'] == 'domainkey') {
					$headers['X-DOMAIN'] = $this->auth['domain'];
					$headers['X-DOMAIN-KEY'] = $this->auth['key'];
				} else if ($this->auth['type'] == 'userpass') {
					$options['auth'] = [$this->auth['user'], $this->auth['pass']];
					if (isset($this->auth['2fa'])) {
						$headers['X-2FA-KEY'] = $this->auth['2fa'];
					}
				}
			}

			if ($this->impersonate !== FALSE) {
				if ($this->impersonateType == 'id') {
					$headers['X-IMPERSONATE-ID'] = $this->impersonate;
				} else {
					$headers['X-IMPERSONATE'] = $this->impersonate;
				}
			}

			$url = sprintf('%s/%s/%s', rtrim($this->baseurl, '/'), $this->version, ltrim($apimethod, '/'));

			try {
				if ($method == 'GET') {
					$response = Requests::get($url, $headers, $options);
				} else if ($method == 'POST') {
					$response = Requests::post($url, $headers, json_encode(['data' => $data]), $options);
				} else if ($method == 'DELETE') {
					$response = Requests::delete($url, $headers, $options);
				}
				$data = @json_decode($response->body, TRUE);
			} catch (Requests_Exception $ex) {
				$data = NULL;
			}

			if ($data == NULL) {
				$data = ['error' => 'There was an unknown error.'];
			}

			if ($this->isDebug()) {
				$debug = ['request' => '', 'response' => $response->body];
				if ($method == 'POST') {
					$debug['request'] = json_encode(['data' => $data]);
				}

				$data['__DEBUG'] = $debug;
			}

			$this->lastResponse = $data;
			return $data;
		}
	}