<?php
function jzzf_list_form() {
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}jzzf_form ORDER BY `title`";
    $results = $wpdb->get_results($sql);
    if($results) {
        foreach($results as $obj) {
    $obj->id = intval($obj->id);
    $obj->theme = intval($obj->theme);
    $obj->realtime = (bool) ($obj->realtime);
            $obj->elements = jzzf_list_element($obj->id);
            $obj->email = jzzf_get_email($obj->id);
        }
    }
    return $results;
}
function jzzf_get_form($key) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_form WHERE `id`=%d";
    $sql = $wpdb->prepare($query, $key);
    $obj = $wpdb->get_row($sql);
    if($obj) {
    $obj->id = intval($obj->id);
    $obj->theme = intval($obj->theme);
    $obj->realtime = (bool) ($obj->realtime);
        $obj->elements = jzzf_list_element($obj->id);
        $obj->email = jzzf_get_email($obj->id);
    }
    return $obj;
}
function jzzf_get_form_by_name($key) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_form WHERE `name`=%s";
    $sql = $wpdb->prepare($query, $key);
    $obj = $wpdb->get_row($sql);
    if($obj) {
    $obj->id = intval($obj->id);
    $obj->theme = intval($obj->theme);
    $obj->realtime = (bool) ($obj->realtime);
        $obj->elements = jzzf_list_element($obj->id);
        $obj->email = jzzf_get_email($obj->id);
    }
    return $obj;
}

function jzzf_set_form($obj) {
    global $wpdb;
    if($obj->id) {
        $query = "UPDATE {$wpdb->prefix}jzzf_form SET `title`=%s,`name`=%s,`theme`=%d,`css`=%s,`realtime`=%d WHERE id=%d";
        $sql = $wpdb->prepare($query, $obj->title,$obj->name,$obj->theme,$obj->css,$obj->realtime, $obj->id);
        $result = $wpdb->query($sql);
        $id = $obj->id;
    } else {
        $query = "INSERT INTO {$wpdb->prefix}jzzf_form (`title`,`name`,`theme`,`css`,`realtime`) VALUES (%s,%s,%d,%s,%d)";
        $sql = $wpdb->prepare($query, $obj->title,$obj->name,$obj->theme,$obj->css,$obj->realtime);
        $result = $wpdb->query($sql);
        $id = $wpdb->insert_id;
    }
    if($result !== false) {
        if(is_array($obj->elements)) {
            $placeholders = array();
            $values = array();
            foreach($obj->elements as $child) {
                $placeholders[] = '%d';
                $values[] = $child->id;
            }
            $query = "SELECT id FROM {$wpdb->prefix}jzzf_element WHERE `form` = %d";
            if($placeholders) {
                $query .= ' AND id NOT IN (' . implode(',', $placeholders) . ')';
            }
            array_unshift($values, $obj->id);
            $sql = $wpdb->prepare($query, $values);
            $orphans = $wpdb->get_col($sql);
            foreach($orphans as $orphan) {
                jzzf_delete_element($orphan);
            }
            foreach($obj->elements as $child) {
                $child->form = $id;
                jzzf_set_element($child);
            }
        }
        $previous = jzzf_get_email($id);
        if($obj->email) {
            $obj->email->form = $id;
            $obj->email->id = $previous ? $previous->id : 0;
            jzzf_set_email($obj->email);
        } else {
            if($previous) {
                jzzf_delete_email($previous->id);
            }
        }
        return $id;
    }
    return false;
}
function jzzf_delete_form($id) {
    global $wpdb;
    $query = "DELETE FROM {$wpdb->prefix}jzzf_form WHERE id = %d";
    $sql = $wpdb->prepare($query, $id);
    if(false === $wpdb->query($sql)) {
        return false;
    }
    $query = "SELECT id FROM {$wpdb->prefix}jzzf_element WHERE form = %d";
    $sql = $wpdb->prepare($query, $id);
    $children = $wpdb->get_col($sql);
    if(is_array($children)) {
        foreach($children as $child) {
            jzzf_delete_element($child);
        }
    }
    $query = "SELECT id FROM {$wpdb->prefix}jzzf_email WHERE form = %d";
    $sql = $wpdb->prepare($query, $id);
    $children = $wpdb->get_col($sql);
    if(is_array($children)) {
        foreach($children as $child) {
            jzzf_delete_email($child);
        }
    }
    return true;
}



function jzzf_set_email($obj) {
    global $wpdb;
    if($obj->id) {
        $query = "UPDATE {$wpdb->prefix}jzzf_email SET `form`=%d,`to`=%s,`cc`=%s,`bcc`=%s,`from`=%s,`subject`=%s,`message`=%s,`sending`=%s,`ok`=%s,`fail`=%s WHERE id=%d";
        $sql = $wpdb->prepare($query, $obj->form,$obj->to,$obj->cc,$obj->bcc,$obj->from,$obj->subject,$obj->message,$obj->sending,$obj->ok,$obj->fail, $obj->id);
        $result = $wpdb->query($sql);
        $id = $obj->id;
    } else {
        $query = "INSERT INTO {$wpdb->prefix}jzzf_email (`form`,`to`,`cc`,`bcc`,`from`,`subject`,`message`,`sending`,`ok`,`fail`) VALUES (%d,%s,%s,%s,%s,%s,%s,%s,%s,%s)";
        $sql = $wpdb->prepare($query, $obj->form,$obj->to,$obj->cc,$obj->bcc,$obj->from,$obj->subject,$obj->message,$obj->sending,$obj->ok,$obj->fail);
        $result = $wpdb->query($sql);
        $id = $wpdb->insert_id;
    }
    return false;
}
function jzzf_delete_email($id) {
    global $wpdb;
    $query = "DELETE FROM {$wpdb->prefix}jzzf_email WHERE id = %d";
    $sql = $wpdb->prepare($query, $id);
    if(false === $wpdb->query($sql)) {
        return false;
    }
    return true;
}


function jzzf_get_email($key) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_email WHERE `form`=%d";
    $sql = $wpdb->prepare($query, $key);
    $obj = $wpdb->get_row($sql);
    if($obj) {
    $obj->id = intval($obj->id);
    $obj->form = intval($obj->form);
    }
    return $obj;
}
function jzzf_list_element($parent) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_element WHERE form='%d' ORDER BY `order`";
    $sql = $wpdb->prepare($query, $parent);
    $results = $wpdb->get_results($sql);
    if($results) {
        foreach($results as $obj) {
    $obj->id = intval($obj->id);
    $obj->form = intval($obj->form);
    $obj->order = intval($obj->order);
    $obj->external = (bool) ($obj->external);
    $obj->visible = intval($obj->visible);
    $obj->zeros = intval($obj->zeros);
    $obj->decimals = intval($obj->decimals);
    $obj->fixed = (bool) ($obj->fixed);
    $obj->divisions = intval($obj->divisions);
    $obj->break = (bool) ($obj->break);
            $obj->options = jzzf_list_option($obj->id);
        }
    }
    return $results;
}
function jzzf_get_element($key) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_element WHERE `id`=%d";
    $sql = $wpdb->prepare($query, $key);
    $obj = $wpdb->get_row($sql);
    if($obj) {
    $obj->id = intval($obj->id);
    $obj->form = intval($obj->form);
    $obj->order = intval($obj->order);
    $obj->external = (bool) ($obj->external);
    $obj->visible = intval($obj->visible);
    $obj->zeros = intval($obj->zeros);
    $obj->decimals = intval($obj->decimals);
    $obj->fixed = (bool) ($obj->fixed);
    $obj->divisions = intval($obj->divisions);
    $obj->break = (bool) ($obj->break);
        $obj->options = jzzf_list_option($obj->id);
    }
    return $obj;
}

function jzzf_set_element($obj) {
    global $wpdb;
    if($obj->id) {
        $query = "UPDATE {$wpdb->prefix}jzzf_element SET `form`=%d,`order`=%d,`type`=%s,`title`=%s,`name`=%s,`formula`=%s,`value`=%s,`value2`=%s,`default`=%s,`external`=%d,`params`=%s,`visible`=%d,`prefix`=%s,`postfix`=%s,`zeros`=%d,`decimals`=%d,`fixed`=%d,`thousands`=%s,`point`=%s,`classes`=%s,`divisions`=%d,`break`=%d WHERE id=%d";
        $sql = $wpdb->prepare($query, $obj->form,$obj->order,$obj->type,$obj->title,$obj->name,$obj->formula,$obj->value,$obj->value2,$obj->default,$obj->external,$obj->params,$obj->visible,$obj->prefix,$obj->postfix,$obj->zeros,$obj->decimals,$obj->fixed,$obj->thousands,$obj->point,$obj->classes,$obj->divisions,$obj->break, $obj->id);
        $result = $wpdb->query($sql);
        $id = $obj->id;
    } else {
        $query = "INSERT INTO {$wpdb->prefix}jzzf_element (`form`,`order`,`type`,`title`,`name`,`formula`,`value`,`value2`,`default`,`external`,`params`,`visible`,`prefix`,`postfix`,`zeros`,`decimals`,`fixed`,`thousands`,`point`,`classes`,`divisions`,`break`) VALUES (%d,%d,%s,%s,%s,%s,%s,%s,%s,%d,%s,%d,%s,%s,%d,%d,%d,%s,%s,%s,%d,%d)";
        $sql = $wpdb->prepare($query, $obj->form,$obj->order,$obj->type,$obj->title,$obj->name,$obj->formula,$obj->value,$obj->value2,$obj->default,$obj->external,$obj->params,$obj->visible,$obj->prefix,$obj->postfix,$obj->zeros,$obj->decimals,$obj->fixed,$obj->thousands,$obj->point,$obj->classes,$obj->divisions,$obj->break);
        $result = $wpdb->query($sql);
        $id = $wpdb->insert_id;
    }
    if($result !== false) {
        if(is_array($obj->options)) {
            $placeholders = array();
            $values = array();
            foreach($obj->options as $child) {
                $placeholders[] = '%d';
                $values[] = $child->id;
            }
            $query = "SELECT id FROM {$wpdb->prefix}jzzf_option WHERE `element` = %d";
            if($placeholders) {
                $query .= ' AND id NOT IN (' . implode(',', $placeholders) . ')';
            }
            array_unshift($values, $obj->id);
            $sql = $wpdb->prepare($query, $values);
            $orphans = $wpdb->get_col($sql);
            foreach($orphans as $orphan) {
                jzzf_delete_option($orphan);
            }
            foreach($obj->options as $child) {
                $child->element = $id;
                jzzf_set_option($child);
            }
        }
        return $id;
    }
    return false;
}
function jzzf_delete_element($id) {
    global $wpdb;
    $query = "DELETE FROM {$wpdb->prefix}jzzf_element WHERE id = %d";
    $sql = $wpdb->prepare($query, $id);
    if(false === $wpdb->query($sql)) {
        return false;
    }
    $query = "SELECT id FROM {$wpdb->prefix}jzzf_option WHERE element = %d";
    $sql = $wpdb->prepare($query, $id);
    $children = $wpdb->get_col($sql);
    if(is_array($children)) {
        foreach($children as $child) {
            jzzf_delete_option($child);
        }
    }
    return true;
}


function jzzf_list_option($parent) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}jzzf_option WHERE element='%d' ORDER BY `order`";
    $sql = $wpdb->prepare($query, $parent);
    $results = $wpdb->get_results($sql);
    if($results) {
        foreach($results as $obj) {
    $obj->id = intval($obj->id);
    $obj->order = intval($obj->order);
    $obj->element = intval($obj->element);
    $obj->default = (bool) ($obj->default);
        }
    }
    return $results;
}

function jzzf_set_option($obj) {
    global $wpdb;
    if($obj->id) {
        $query = "UPDATE {$wpdb->prefix}jzzf_option SET `order`=%d,`element`=%d,`default`=%d,`title`=%s,`name`=%s,`value`=%s WHERE id=%d";
        $sql = $wpdb->prepare($query, $obj->order,$obj->element,$obj->default,$obj->title,$obj->name,$obj->value, $obj->id);
        $result = $wpdb->query($sql);
        $id = $obj->id;
    } else {
        $query = "INSERT INTO {$wpdb->prefix}jzzf_option (`order`,`element`,`default`,`title`,`name`,`value`) VALUES (%d,%d,%d,%s,%s,%s)";
        $sql = $wpdb->prepare($query, $obj->order,$obj->element,$obj->default,$obj->title,$obj->name,$obj->value);
        $result = $wpdb->query($sql);
        $id = $wpdb->insert_id;
    }
    return false;
}
function jzzf_delete_option($id) {
    global $wpdb;
    $query = "DELETE FROM {$wpdb->prefix}jzzf_option WHERE id = %d";
    $sql = $wpdb->prepare($query, $id);
    if(false === $wpdb->query($sql)) {
        return false;
    }
    return true;
}


