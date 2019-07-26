<?php

  /**
   * Collections of method that helps to manage the data.
   * All methods in this trait should be private.
   *
   */
  trait HelpersTrait {

    private function init( $conf = false ) {
      // Check for valid configurations.
      if( empty( $conf ) OR !is_array( $conf ) ) throw new \Exception( 'Invalid configurations was found.' );
      // Check if the 'data_directory' was provided.
      if ( !isset( $conf[ 'data_directory' ] ) ) throw new \Exception( '"data_directory" was not provided in the configurations.' );
      // Check if data_directory is empty.
      if ( empty( $conf[ 'data_directory' ] ) ) throw new \Exception( '"data_directory" cant be empty in the configurations.' );
      // Prepare the data directory.
      $dataDir = trim( $conf[ 'data_directory' ] );
      // Handle directory path ending.
      if ( substr( $dataDir, -1 ) !== '/' ) $dataDir = $dataDir . '/';
      // Check if the data_directory exists.
      if ( !file_exists( $dataDir ) ) {
        // The directory was not found, create one.
        if ( ! (mkdir( $dataDir, 7777, true ) && chown($dataDir,'www-data')) ) throw new \Exception( 'Unable to create the data directory at ' . $dataDir );
      }
      
      clearstatcache();
      
      // Check if PHP has write permission in that directory.
      #if ( !is_writable( $dataDir ) ) throw new \Exception( 'Data directory is not writable at "' . $dataDir . '." Please change data directory permission.' );
      $start = microtime(true);
      while (!is_writable($dataDir)) {
        usleep(100000);
	if(microtime(true) - $start > 1.0) throw new \Exception( 'Store path is not writable at "' . $dataDir . '." Please change store path permission.' );
      }
      // Finally check if the directory is readable by PHP.
      if ( !is_readable( $dataDir ) ) throw new \Exception( 'Data directory is not readable at "' . $dataDir . '." Please change data directory permission.' );
      // Set the data directory.
      $this->dataDirectory = $dataDir;
      // Set auto cache settings.
      $autoCache = true;
      if ( isset( $conf[ 'auto_cache' ] ) ) $autoCache = $conf[ 'auto_cache' ];
      $this->initAutoCache( $autoCache );
      // Set timeout.
      $timeout = 120;
      if ( isset( $conf[ 'timeout' ] ) ) {
        if ( !empty( $conf[ 'timeout' ] ) ) $timeout = (int) $conf[ 'timeout' ];
      }
      set_time_limit( $timeout );
    } // End of init()

    // Init data that SleekDB required to operate.
    private function initVariables() {
      // Set empty results
      $this->results = [];
      // Set a default limit
      $this->limit = 0;
      // Set a default skip
      $this->skip = 0;
      // Set default conditions
      $this->conditions = [];
      // Set default group by value
      $this->orderBy = [
        'order' => false,
        'field' => '_id'
      ];
      // Set the default search keyword as an empty string.
      $this->searchKeyword = '';
      // Disable make cache by default.
      $this->makeCache = false;
    } // End of initVariables()

    // Initialize the auto cache settings.
    private function initAutoCache ( $autoCache = true ) {
      // Decide the cache status.
      if ( $autoCache === true ) {
        $this->useCache = true;
        // A flag that is used to check if cache should be empty
        // while create a new object in a store.
        $this->deleteCacheOnCreate = true;
      } else {
        $this->useCache = false;
        // A flag that is used to check if cache should be empty 
        // while create a new object in a store.
        $this->deleteCacheOnCreate = false;
      }
    }

    // Method to boot a store.
    private function bootStore() {
      $store = trim( $this->storeName );
      // Validate the store name.
      if ( !$store || empty( $store ) ) throw new \Exception( 'Invalid store name was found' );
      // Prepare store name.
      if ( substr( $store, -1 ) !== '/' ) $store = $store . '/';
      // Store directory path.
      $this->storePath = $this->dataDirectory . $store;
      // Check if the store exists.
      if ( !file_exists( $this->storePath ) ) {
        // The directory was not found, create one with cache directory.
        if ( !(mkdir( $this->storePath, 7777, true ) && chown($this->storePath,'www-data')) ) throw new \Exception( 'Unable to create the store path at ' . $this->storePath );
        // Create the cache directory.
        if ( !(mkdir( $this->storePath . 'cache', 7777, true ) && chown($this->storePath.'cache','www-data')) ) throw new \Exception( 'Unable to create the store\'s cache directory at ' . $this->storePath . 'cache' );
        // Create the data directory.
        if ( !(mkdir( $this->storePath . 'data', 7777, true ) && chown($this->storePath.'data','www-data')) ) throw new \Exception( 'Unable to create the store\'s data directory at ' . $this->storePath . 'data' );
        // Create the store counter file.
        if ( ! (file_put_contents( $this->storePath . '_cnt.sdb', '0' ) && chown($this->storePath . '_cnt.sdb','www-data')) ) throw new \Exception( 'Unable to create the system counter for the store! Please check write permission' );
      }
#      clearstatcache(true,$this->storePath);
      clearstatcache();
      // Check if PHP has write permission in that directory.
      #if ( !is_writable( $this->storePath ) ) throw new \Exception( 'Store path is not writable at "' . $this->storePath . '." Please change store path permission.' );
      $start = microtime(true);
      while (!is_writable($this->storePath)) {
        usleep(100000);
	if(microtime(true) - $start > 1.0) throw new \Exception( 'Store path is not writable at "' . $this->storePath . '." Please change store path permission.' );
      }
      // Finally check if the directory is readable by PHP.
      if ( !is_readable( $this->storePath ) ) throw new \Exception( 'Store path is not readable at "' . $this->storePath . '." Please change store path permission.' );
    }

    // Returns a new and unique store object ID, by calling this method it would also
    // increment the ID system-wide only for the store.
    private function getStoreId() {
      $counterPath = $this->storePath . '_cnt.sdb';
      if ( file_exists( $counterPath ) ) {
        $counter = (int) file_get_contents( $counterPath );
      } else {
        $counter = 0;
      }
      $counter++;
      file_put_contents( $counterPath, $counter );
      return $counter;
    }

    // Return the last created store object ID.
    private function getLastStoreId() {
      $counterPath = $this->storePath . '_cnt.sdb';
      if ( file_exists( $counterPath ) ) {
        return (int) file_get_contents( $counterPath );
      }
    }

    // Get a store by its system id. "_id"
    private function getStoreDocumentById( $id ) {
      $store = $this->storePath . 'data/' . $id . '.json';
      if ( file_exists( $store ) ) {
        $data = json_decode( file_get_contents( $store ), true );
        if ( $data !== false ) return $data;
      }
      return [];
    }

    // Find store objects with conditions, sorting order, skip and limits.
    private function findStoreDocuments() {
      $found          = [];
      $lastStoreId    = $this->getLastStoreId();
      $searchRank     = [];
      // Start collecting and filtering data.
      for ( $i = 0; $i <= $lastStoreId; $i++ ) {
        // Collect data of current iteration.
        $data = $this->getStoreDocumentById( $i );
        if ( ! empty( $data ) ) {
          // Filter data found.
          if ( empty( $this->conditions ) ) {
            // Append all data of this store.
            $found[] = $data;
          } else {
            // Append only passed data from this store.
            $storePassed = true;
            // Iterate each conditions.
            foreach ( $this->conditions as $condition ) {
              // Check for valid data from data source.
              $validData = true;
              $fieldValue = '';
              try {
                $fieldValue = $this->getNestedProperty( $condition[ 'fieldName' ], $data );
              } catch( \Exception $e ) {
                $validData   = false;
                $storePassed = false;
              }
              if( $validData === true ) {
                // Check the type of rule.
                if ( $condition[ 'condition' ] === '=' ) {
                  // Check equal.
                  if ( $fieldValue != $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '!=' ) {
                  // Check not equal.
                  if ( $fieldValue == $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '>' ) {
                  // Check greater than.
                  if ( $fieldValue <= $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '>=' ) {
                  // Check greater equal.
                  if ( $fieldValue < $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '<' ) {
                  // Check less than.
                  if ( $fieldValue >= $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '<=' ) {
                  // Check less equal.
                  if ( $fieldValue > $condition[ 'value' ] ) $storePassed = false;
                }
              }
            }
            // Check if current store is updatable or not.
            if ( $storePassed === true ) {
              // Append data to the found array.
              $found[] = $data;
            }
          }
        }
      }
      if ( count( $found ) > 0 ) {
        // Check do we need to sort the data.
        if ( $this->orderBy[ 'order' ] !== false ) {
          // Start sorting on all data.
          $found = $this->sortArray( $this->orderBy[ 'field' ], $found, $this->orderBy[ 'order' ] );
        }
        // If there was text search then we would also sort the result by search ranking.
        if ( ! empty( $this->searchKeyword ) ) {
          $found = $this->performSerach( $found );
        }
        // Skip data
        if ( $this->skip > 0 ) $found = array_slice( $found, $this->skip );
        // Limit data.
        if ( $this->limit > 0 ) $found = array_slice( $found, 0, $this->limit );
      }
      return $found;
    }

    // Writes an object in a store.
    private function writeInStore( $storeData ) {
      // Cast to array
      $storeData = (array) $storeData;
      // Check if it has _id key
      if ( isset( $storeData[ '_id' ] ) ) throw new \Exception( 'The _id index is reserved by SleekDB, please delete the _id key and try again' );
      $id = $this->getStoreId();
      // Add the system ID with the store data array.
      $storeData[ '_id' ] = $id;
      // Prepare storable data
      $storableJSON = json_encode( $storeData );
      if ( $storableJSON === false ) throw new \Exception( 'Unable to encode the data array, 
        please provide a valid PHP associative array' );
      // Define the store path
      $storePath = $this->storePath . 'data/' . $id . '.json';
      if ( ! file_put_contents( $storePath, $storableJSON ) ) {
        throw new \Exception( "Unable to write the object file! Please check if PHP has write permission." );
      }
      return $storeData;
    }

    // Sort store objects.
    private function sortArray( $field, $data, $order = 'ASC' ) {
      $dryData = [];
      // Check if data is an array.
      if( is_array( $data ) ) {
        // Get value of the target field.
        foreach ( $data as $value ) {
          $dryData[] = $this->getNestedProperty( $field, $value );
        }
      }
      // Descide the order direction.
      if ( strtolower( $order ) === 'asc' ) asort( $dryData );
      else if ( strtolower( $order ) === 'desc' ) arsort( $dryData );
      // Re arrange the array.
      $finalArray = [];
      foreach ( $dryData as $key => $value) {
        $finalArray[] = $data[ $key ];
      }
      return $finalArray;
    }

    // Get nested properties of a store object.
    private function getNestedProperty( $field = '', $data ) {
      if( is_array( $data ) AND ! empty( $field ) ) {
        // Dive deep step by step.
        foreach( explode( '.', $field ) as $i ) {
          // If the field do not exists then insert an empty string.
          if ( ! isset( $data[ $i ] ) ) {
            $data = '';
            throw new \Exception( '"'.$i.'" index was not found in the provided data array' );
            break;
          } else {
            // The index is valid, collect the data.
            $data = $data[ $i ];
          }
        }
        return $data;
      }
    }

    // Do a search in store objects. This is like a doing a full-text search.
    private function performSerach( $data = [] ) {
      if ( empty( $data ) ) return $data;
      $nodesRank = [];
      // Looping on each store data.
      foreach ($data as $key => $value) {
        // Looping on each field name of search-able fields.
        foreach ($this->searchKeyword[ 'field' ] as $field) {
          try {
            $nodeValue = $this->getNestedProperty( $field, $value );
            // The searchable field was found, do comparison against search keyword.
            similar_text( strtolower($nodeValue), strtolower($this->searchKeyword['keyword']), $perc );
            if ( $perc > 50 ) {
              // Check if current store object already has a value, if so then add the new value.
              if ( isset( $nodesRank[ $key ] ) ) $nodesRank[ $key ] += $perc;
              else $nodesRank[ $key ] = $perc;
            }
          } catch ( \Exception $e ) {
            continue;
          }
        }
      }
      if ( empty( $nodesRank ) ) {
        // No matched store was found against the search keyword.
        return [];
      }
      // Sort nodes in descending order by the rank.
      arsort( $nodesRank );
      // Map original nodes by the rank.
      $nodes = [];
      foreach ( $nodesRank as $key => $value ) {
        $nodes[] = $data[ $key ];
      }
      return $nodes;
    }
    
  }
  