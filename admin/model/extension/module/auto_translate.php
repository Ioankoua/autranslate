<?php
class ModelExtensionModuleAutoTranslate extends Model {
    private $url = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&';

    public function translate($from, $to, $text) {
        if (trim($text) == '') {
            return '';
        }
        
        try {
            $headers = array(
                'Content-Type: application/json'
            );

            $url = "https://www.googleapis.com/language/translate/v2";

            $values = array(
                'key'    => $this->config->get('module_auto_translate_key'),
                'target' => $this->config->get('module_auto_translate_code'),
                'q'      => $text
                );

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $values);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
            $response = curl_exec($handle);                         
            curl_close($handle);

            $result = json_decode($response, true);

            if($result['data']['translations'][0]['translatedText']) {
                return $result['data']['translations'][0]['translatedText'];
            } else {
                return 'Error!';
            }
        } catch(exception $exception){
            $this->log->write('AUTO TRANSLATE ERROR: ' . $exception->getMessage());
            
            return $exception->getMessage();
        }
    }

    public function getTranslateFields($type) {
        switch ($type) {
            case 'product':
                $fields = array (
                    'name' => 'Product Name',
                    'description' => 'Description',
                    'meta_title' => 'Meta Tag Title',
                    'meta_description' => 'Meta Tag Description',
                    'meta_keywords' => 'Meta Tag Keywords'
                );
                break;
            
            default:
                # code...
                break;
        }

        return $fields;
    }

    public function getTotal($type, $from_language) {
        if ($type == 'banner') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "banner_image WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'category') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'information') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "information_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'product') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'option') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "option_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'option_value') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'attribute') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "attribute_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'attribute_group') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "attribute_group_description WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        } elseif ($type == 'product_attribute') {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_attribute WHERE language_id = '" . (int)$from_language['language_id'] . "'");
        }

        return $query->row['total'];
    }

    public function update($type, $from_language, $to_language, $start) {
        if ($type == 'banner') {
            $this->updateBanner($from_language, $to_language, $start);
        } elseif ($type == 'category') {
            $this->updateCategory($from_language, $to_language, $start);
        } elseif ($type == 'information') {
            $this->updateInformation($from_language, $to_language, $start);
        } elseif ($type == 'product') {
            $this->updateProduct($from_language, $to_language, $start);
        } elseif ($type == 'option') {
            $this->updateOption($from_language, $to_language, $start);
        } elseif ($type == 'option_value') {
            $this->updateOptionValue($from_language, $to_language, $start);
        } elseif ($type == 'attribute') {
            $this->updateAttribute($from_language, $to_language, $start);
        } elseif ($type == 'attribute_group') {
            $this->updateAttributeGroup($from_language, $to_language, $start);
        } elseif ($type == 'product_attribute') {
            $this->updateProductAttribute($from_language, $to_language, $start);
        }
    }

    public function updateCategory($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
            $name = $this->translate($from_language, $to_language, $result['name']);

            // Description
            $result['description'] = html_entity_decode($result['description'], ENT_QUOTES);

            $description = $this->translate($from_language, $to_language, $result['description']);
            
            // Meta title
            $meta_title = $this->translate($from_language, $to_language, $result['meta_title']);

            // Meta Description
            $meta_description = $this->translate($from_language, $to_language, $result['meta_description']);

            // Meta Keywords
            $meta_keyword = $this->translate($from_language, $to_language, $result['meta_keyword']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$result['category_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$result['category_id'] . "', language_id = '" . (int)$to_language['language_id'] . "', name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "category_description SET name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "' WHERE category_id = '" . (int)$result['category_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateInformation($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY title ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Title
            $title = $this->translate($from_language, $to_language, $result['title']);
            
            // Meta title
            $meta_title = $this->translate($from_language, $to_language, $result['meta_title']);
            
            // Meta description
            $meta_description = $this->translate($from_language, $to_language, $result['meta_description']);
            
            // Meta Keyword
            $meta_keyword = $this->translate($from_language, $to_language, $result['meta_keyword']);

            // Description
            $result['description'] = html_entity_decode($result['description'], ENT_QUOTES);

            $description = $this->translate($from_language, $to_language, $result['description']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_description WHERE information_id = '" . (int)$result['information_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "information_description SET information_id = '" . (int)$result['information_id'] . "', language_id = '" . (int)$to_language['language_id'] . "', title = '" . $this->db->escape($title) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "information_description SET title = '" . $this->db->escape($title) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "' WHERE information_id = '" . (int)$result['information_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateProduct($from_language, $to_language, $start) {
       
        $from_language_id = $this->getLanguageIdByCode($from_language);
        $to_language_id = $this->getLanguageIdByCode($to_language);

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE language_id = '" . $from_language . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
       
            $name = $this->translate($from_language, $to_language, $result['name']);
            
            // Description
            $result['description'] = html_entity_decode($result['description'], ENT_QUOTES);

            if (utf8_strlen($result['description']) > 10000) {
                $middle = utf8_strrpos(utf8_substr($result['description'], 0, floor(utf8_strlen($result['description']) / 2)), ' ') + 1;

                $string1 = utf8_substr($result['description'], 0, $middle);
                $string2 = utf8_substr($result['description'], $middle);

                $request = array(
                    'text'        => $string1,
                    'from'        => $from_language['code'],
                    'to'          => $to_language['code'],
                    'contentType' => 'text/html'
                );
                
                $string1 = $this->translate($from_language, $to_language, $string1);
                $string2 = $this->translate($from_language, $to_language, $string2);
                $description = $string1 . ' ' . $string2;
            } else {
                $description = $this->translate($from_language, $to_language, $result['description']);
            }

            // Meta title
            $meta_title = $this->translate($from_language, $to_language, $result['meta_title']);

            // Meta Description
            $meta_description = $this->translate($from_language, $to_language, $result['meta_description']);

            // Meta Keywords
            $meta_keyword = $this->translate($from_language, $to_language, $result['meta_keyword']);

            // Tags
            $tag = $this->translate($from_language, $to_language, $result['tag']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$result['product_id'] . "' AND language_id = '" . $TO_LANGUAGE . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$result['product_id'] . "', language_id = '" . $TO_LANGUAGE . "', name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "', tag = '" . $this->db->escape($tag) . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "', tag = '" . $this->db->escape($tag) . "' WHERE product_id = '" . (int)$result['product_id'] . "' AND language_id = '" . $TO_LANGUAGE . "'");
            }
        }
    }

    public function getLanguageIdByCode($code) {
		$query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE code = '" . $code . "'");

		return $query->row;
	}
    
    public function translateProduct($from_language, $to_language, $data, $translation_options) {
        $translations = array();
        if($translation_options['name']) {
            $translations['name'] = $this->translate($from_language, $to_language, $data['name']);
        }

        if($translation_options['description']) {
            $translations['description'] = html_entity_decode($this->translate($from_language, $to_language, $data['description']), ENT_QUOTES);
        }

        if($translation_options['meta_title']) {
            $translations['meta_title'] = $this->translate($from_language, $to_language, $data['meta_title']);
        }

        if($translation_options['meta_description']) {
            $translations['meta_description'] = $this->translate($from_language, $to_language, $data['meta_description']);
        }

        if($translation_options['meta_keywords']) {
            $translations['meta_keywords'] = $this->translate($from_language, $to_language, $data['meta_keywords']);
        }

        return $translations;  
    }
}