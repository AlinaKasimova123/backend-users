<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
        return [
        'id' => (int) $row['user_id'],
        'access' => (int) $row['access']
        ];
        } else {
        return [
        'id' => 0,
        'access' => 0
        ];
        }
    }

    public static function get_user_info($d) {
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, access, email FROM users WHERE user_id='".$d."' LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => phone_formatting($row['phone']),
                'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
                'plot_id' => '',
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'access' => 0,
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        
        if ($search) $where[] = "first_name LIKE '%".$search."%' or email LIKE '%".$search."%' or phone LIKE '%".$search."%'";
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone, last_login
            FROM users ".$where." ORDER BY user_id desc LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $last_login = $row['last_login'] == 0 ? '' : date('d M Y H:i', $row['last_login']);
            $items[] = [
                'id' => (int) $row['user_id'],
                'plots' => explode(',', $row['plot_id']),
                'plot_id' => (int) $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => phone_formatting($row['phone']),
                'last_login' => $last_login,
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users';
        if ($search) $url .= '?search='.$search.'&';
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    // ACTIONS

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::get_user_info($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
    // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $plot_id = isset($d['plot_id']) && is_string($d['plot_id']) ? $d['plot_id'] : '';
        $first_name = isset($d['first_name']) && is_string($d['first_name']) ? $d['first_name'] : '';
        $last_name = isset($d['last_name']) && is_string($d['last_name']) ? $d['last_name'] : '';
        $email = isset($d['email']) && is_string($d['email']) ? $d['email'] : '';
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // update
        if ($user_id) {
            $set = [];
            $set[] = "plot_id='".$plot_id."'";
            $set[] = "first_name='".$first_name."'";
            $set[] = "last_name='".$last_name."'";
            $set[] = "email='".strtolower($email)."'";
            $set[] = "phone='".$phone."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
        } else {
            DB::query("INSERT INTO users (
                plot_id,
                first_name,
                last_name,
                email,
                phone
            ) VALUES (
                '".$plot_id."',
                '".$first_name."',
                '".$last_name."',
                '".strtolower($email)."',
                '".$phone."'
            );") or die (DB::error());
        }
        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public static function user_delete_info($d) {
        $q = DB::query("SELECT user_id FROM users WHERE user_id='".$d."' LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name']
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => ''
            ];
        }
    }

    public static function user_delete_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::get_user_info($user_id));
        return ['html' => HTML::fetch('./partials/delete_window.html')];
    }

    public static function user_delete($d = []) {
        // vars
            $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
            $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
            // delete
            if ($user_id) {
                DB::query("DELETE FROM users
                    WHERE user_id='".$user_id."';") or die (DB::error());
            }
            // output
            return User::users_fetch(['offset' => $offset]);
    }

    public static function error_window($d = []){
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::get_user_info($user_id));
        return ['html' => HTML::fetch('./partials/error_window.html')];
    }
}

