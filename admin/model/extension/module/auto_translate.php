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
                'target' => "uk",
                'q'      => $text
                );

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);     //We want the result to be saved into variable, not printed out
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

    public function updateBanner($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY banner_id ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            $title = $this->translate($from_language, $to_language, $result['title']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$result['banner_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "banner_image SET title = '" . $this->db->escape($title) . "', banner_id = '" . (int)$result['banner_id'] . "', language_id = '" . (int)$to_language['language_id'] . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "banner_image SET title = '" . $this->db->escape($title) . "' WHERE banner_id = '" . (int)$result['banner_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
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

    public function updateProductAttribute($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY product_id ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Text
            $text = $this->translate($from_language, $to_language, $result['text']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$result['product_id'] . "' AND attribute_id = '" . (int)$result['attribute_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$result['product_id'] . "', attribute_id = '" . (int)$result['attribute_id'] . "', language_id = '" . (int)$to_language['language_id'] . "', text = '" . $this->db->escape($text) . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "product_attribute SET text = '" . $this->db->escape($text) . "' WHERE product_id = '" . (int)$result['product_id'] . "' AND attribute_id = '" . (int)$result['attribute_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateOption($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
            $name = $this->translate($from_language, $to_language, $result['name']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_description WHERE option_id = '" . (int)$result['option_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET name = '" . $this->db->escape($name) . "', option_id = '" . (int)$result['option_id'] . "', language_id = '" . (int)$to_language['language_id'] . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "option_description SET name = '" . $this->db->escape($name) . "' WHERE option_id = '" . (int)$result['option_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateOptionValue($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
            $name = $this->translate($from_language, $to_language, $result['name']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value_description WHERE option_value_id = '" . (int)$result['option_value_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET name = '" . $this->db->escape($name) . "', option_id = '" . (int)$result['option_id'] . "', option_value_id = '" . (int)$result['option_value_id'] . "', language_id = '" . (int)$to_language['language_id'] . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "option_value_description SET name = '" . $this->db->escape($name) . "' WHERE option_value_id = '" . (int)$result['option_value_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateAttribute($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
            $name = $this->translate($from_language, $to_language, $result['name']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_description WHERE attribute_id = '" . (int)$result['attribute_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET name = '" . $this->db->escape($name) . "', attribute_id = '" . (int)$result['attribute_id'] . "', language_id = '" . (int)$to_language['language_id'] . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "attribute_description SET name = '" . $this->db->escape($name) . "' WHERE attribute_id = '" . (int)$result['attribute_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function updateAttributeGroup($from_language, $to_language, $start) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_group_description WHERE language_id = '" . (int)$from_language['language_id'] . "' ORDER BY name ASC LIMIT " . (int)$start . ",10");

        foreach ($query->rows as $result) {
            // Name
            $name = $this->translate($from_language, $to_language, $result['name']);

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_group_description WHERE attribute_group_id = '" . (int)$result['attribute_group_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");

            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group_description SET name = '" . $this->db->escape($name) . "', attribute_group_id = '" . (int)$result['attribute_group_id'] . "', language_id = '" . (int)$to_language['language_id'] . "'");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "attribute_group_description SET name = '" . $this->db->escape($name) . "' WHERE attribute_group_id = '" . (int)$result['attribute_group_id'] . "' AND language_id = '" . (int)$to_language['language_id'] . "'");
            }
        }
    }

    public function getLanguageIdByCode($code) {
		$query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE code = '" . $code . "'");

		return $query->row;
	}

    public function getOption() {
		$query = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_value");

		return $query->rows;
	}
    
    public function translateOneProduct($from_language, $to_language, $data) {

        $name = $this->translate($from_language, $to_language, $data['name']);
        $description = $this->translate($from_language, $to_language, $data['description']);
        $meta_title = $this->translate($from_language, $to_language, $data['meta_title']);
        $description = html_entity_decode($description, ENT_QUOTES);
        

        return $result[] = ['name' => $name, 'description' => $description, 'meta_title' => $meta_title];  
    }

    public function translateOption($from_language, $to_language, $data) {

        $name = $this->translate($from_language, $to_language, $data['name']);
        if(isset($data['option'])){
            foreach ($data['option'] as $key) {
                $option[] = $this->translate($from_language, $to_language, $key);
            }
        }else{
            $option = false;
        }

        return $result[] = ['name' => $name, 'option' => $option];  
    }
}