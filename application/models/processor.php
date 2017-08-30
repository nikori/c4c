<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class processor extends CI_Model {

    function receiver_processor() {
//$text ;
        $queryone = $this->db->query("SELECT mobile_no,date_received,level,msg,sms_status,id FROM tbl_logs_inbox WHERE sms_status='1' ORDER BY id DESC");
        if ($queryone->num_rows() > 0) {
            $mobile_no = $queryone->result();
            foreach ($mobile_no as $x) {

                $mno = $x->mobile_no;
                $level = $x->level;
                $new_level = $level + 1;
                $message_id = $new_level + 1;
                $m_text = $x->msg;
//Remove whitespaces.
//$text=preg_replace('/[^0-9a-zA-Z_]/',"",$m_text);
                $text = preg_replace('/\s\s+/', ' ', $m_text);
                $a_explode = explode("*", $m_text);
                $reg_rep_uc = $a_explode[0];
                $reg_rep_android = strtoupper($reg_rep_uc);
//check if mobile number exists on tbl_patientdetails.
                if ($this->check_if_mobile_exists($mno) == "exists" && $reg_rep_android == "REG" && $reg_rep_android != "REP") {
                    $hcw_p_level = $this->getLevel($mno, $level);
                    foreach ($hcw_p_level as $m) {
                        $p_level = $m->level;
                        $hcw_id = $m->id;
                        echo "Health care worker level " . $p_level . "</br>";

//Didn't complete registration earlier so this completes the registration through android app
                        if ($p_level < 5) {
                            $msg = explode("*", $text);
                            $f_name = $msg[1];
                            $l_name = $msg[2];
                            $national_id = $msg[3];
                            $dob = $msg[4];
                            $gender = $msg[5];
                            $cadre = $msg[6];
                            $facility_id = $msg[7];
                            $hepatitis_b = $msg[8];
                            $u_name = $msg[9];
                            $password = $msg[10];

                            $a_explode = explode("/", $dob);
                            $yob = $a_explode[2];

                            if (is_string($f_name) && is_string($u_name) && ctype_alnum($password) && ctype_alnum($l_name) && ctype_digit($national_id) && (ctype_digit($gender) && strlen($gender) == '1') && (ctype_digit($cadre) && strlen($cadre) == '1') && (ctype_digit($facility_id) && strlen($facility_id) == '5')) {
                                $this->android_register_update($mno, $cadre, $national_id, $l_name, $f_name, $gender, $yob, $facility_id, $hepatitis_b, $u_name, $password);
                                echo "Registration through android app was successfull" . "</br>";
                            }
                        }
//Double registration on tbl_patietdetails- send: you are already registered                
                        else {
//Send -you are already registered in c4c ,Duplicate registration
                            $msg_id = 60;
                            $this->confirmatory_message_outbox($mno, $msg_id);
                        }
                    }
                }
                if ($this->check_if_mobile_exists($mno) == "empty" && $reg_rep_android == "REG" && $reg_rep_android != "REP") {

//First time registration through android

                    $msg = explode("*", $text);
                    $f_name = $msg[1];
                    $l_name = $msg[2];
                    $national_id = $msg[3];
                    $dob = $msg[4];
                    $gender = $msg[5];
                    $cadre = $msg[6];
                    $facility_id = $msg[7];
                    $hepatitis_b = $msg[8];
                    $u_name = $msg[9];
                    $password = $msg[10];

                    $a_explode = explode("/", $dob);
                    $yob = $a_explode[2];
                    if (is_string($f_name) && is_string($u_name) && ctype_alnum($password) && is_string($l_name) && ctype_digit($national_id) && (ctype_digit($gender) && strlen($gender) == '1') && (ctype_digit($cadre) && strlen($cadre) == '1') && (ctype_digit($facility_id) && strlen($facility_id) == '5')) {
                        $this->android_register($mno, $cadre, $national_id, $l_name, $f_name, $gender, $yob, $facility_id, $hepatitis_b, $u_name, $password);
                        echo "Registration through android app was successfull" . "</br>";
                    } else {
                        echo 'Invalid data  ' . $mno . '</br>';
                    }
                }
//Process keyword PEP once received on tbl_logs_inbox.
                if ($this->check_if_mobile_exists($mno) == "exists" && $reg_rep_android != "REG" && $reg_rep_android != "REP" && $reg_rep_android == "C4C") {
                    $hcw_p_level = $this->getLevel($mno, $level);
                    foreach ($hcw_p_level as $m) {
                        $p_level = $m->level;
                        $hcw_id = $m->id;
                        echo "Health care worker levl " . $p_level . "</br>";
                    }
                    if ($p_level == 1) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 2) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 3) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 4) {
                        $this->confirmatory_message();
                    }
                    if ($p_level == 5) {
                        $this->confirmatory_message();
                    }
                    if ($p_level == 6) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 7) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 8) {
                        $msg_id = $p_level;
                        $this->insert_outbox($mno, $msg_id);
                    }
                    if ($p_level == 9) {
                        $msg_id = $p_level - 3;
                        $new_level = $msg_id;
                        $this->insert_outbox($mno, $msg_id);
//update tbl_patientdetails to level 6 for  a re-exposure report.
                        $this->update_level_patientdetails($mno, $new_level);
                    }
                }
                if ($this->check_if_mobile_exists($mno) == "empty" && $reg_rep_android != "REG" && $reg_rep_android != "REP" && $reg_rep_android == "C4C") {

//process keyword pep sends kindly  send your name

                    $this->insert_outbox_not_registered($mno, $new_level);
                    $this->insert_patientdetails_insert_number($mno, $new_level);

                    echo 'Inserted mobile number to patientdetails and tbl_logs_outbox ; send- welcome to c4c send your name and id ,text=sent';
                }
                if ($this->check_if_mobile_exists($mno) == "empty" && $reg_rep_android != "REG" && $reg_rep_android != "REP" && $reg_rep_android != "C4C") {
//Send pep to start registration -sent when one sends a non pep keyword                    
                    $msg_id = 68;
                    $this->insert_to_outbox($mno, $msg_id);
                    echo 'Inserted to  tbl_logs_outbox- text; Send pep to start reg';
                }
                if ($this->check_if_mobile_exists($mno) == "empty" && $reg_rep_android != "REG" && $reg_rep_android == "REP" && $reg_rep_android != "C4C") {
//Send pep to start registration -sent when one sends a non pep keyword
                    $msg_id = 68;
                    $this->insert_to_outbox($mno, $msg_id);
                    echo 'Inserted to  tbl_logs_outbox- text; Send pep to start reg';
                }
                if ($this->check_if_mobile_exists($mno) == "exists" && $reg_rep_android != "REG" && $reg_rep_android != "REP" && $reg_rep_android != "C4C" && $reg_rep_android != "YES" && $reg_rep_android != "NO") {
                    $h_level = $this->getLevel($mno, $level);
                    foreach ($h_level as $value) {
                        $hcw_p_id = $value->id;
                        $mno = $x->mobile_no;
                        $hcw_r_level = $value->level;

                        if ($hcw_r_level == 9) {
                            $msg_id = 87;
                            $this->insert_to_outbox($mno, $msg_id);
                        } else {
                            $this->insert_or_update_tblpatientdetails($new_level, $text, $level, $mno);
                        }
                    }
                }
//Before accepting an exposure report check if one is fully registered first.
                if ($this->check_if_mobile_exists($mno) == "exists" && $reg_rep_android != "REG" && $reg_rep_android == "REP") {
                    $h_level = $this->getLevel($mno, $level);
                    foreach ($h_level as $value) {
                        $hcw_p_id = $value->id;
                        $mno = $x->mobile_no;
                        $hcw_r_level = $value->level;

                        echo "HCW id on tbl_patientdetails = " . $hcw_p_id . "</br>";


//Checks if one is fully registered on tbl_patientdetails else if not send a text to request one to complete registration
                        if ($hcw_r_level > 3) {
                            $query_e_count = $this->db->query("SELECT re_exposure_count FROM tbl_reports where p_details_id='$hcw_p_id'");
                            if ($query_e_count->num_rows() > 0) {
                                $e_count = $query_e_count->result();
                                foreach ($e_count as $x) {
                                    $count = $x->re_exposure_count;
                                }
                            }

                            if ($this->check_if_exposed_earlier($hcw_p_id) == "Yes") {
                                $current_time = date("Y-m-d H:i:s");
                                $re_ex_count = $count + 1;
//Report a re- exposure through android app or incomplete exposure reported through sms
                                $re_exposure_count = 1;
                                $msg = explode("*", $text);
                                $location = $msg[1];
                                $cause = $msg[2];
                                $hours = $msg[3];



                                echo "Location of exposure = " . $location . "</br>";
                                echo "Exposure cause = " . $cause . "</br>";
                                echo "Hours after exposure = " . $hours . "</br>";
                                echo "Exposure Count = " . $re_ex_count . "</br>";
                                if (ctype_digit($location) && ctype_digit($cause) && (ctype_digit($hours) )) {
                                    $this->android_reexposure($hcw_p_id, $mno, $location, $cause, $hours, $re_ex_count, $count, $current_time);
//insert to tbl_reports_all for visual reports on exposure
                                    $this->android_exposure_report($hcw_p_id, $mno, $location, $cause, $hours, $current_time);
                                    echo "Re-exposure report through android app was successfull" . "</br>";
                                }
                            }
                            if ($this->check_if_exposed_earlier($hcw_p_id) == "No") {
                                $current_time = date("Y-m-d H:i:s");
                                $msg = explode("*", $text);
                                $location = $msg[1];
                                $cause = $msg[2];
                                $hours = $msg[3];

                                echo "Location of exposure = " . $location . "</br>";
                                echo "Exposure cause =" . $cause . "</br>";
                                echo "Hours after exposure = " . $hours . "</br>";
                                if (ctype_digit($location) && ctype_digit($cause) && (ctype_digit($hours) )) {
                                    $this->android_report($hcw_p_id, $mno, $location, $cause, $hours, $current_time);
//insert to tbl_reports_all for visual reports on exposure
                                    $this->android_exposure_report($hcw_p_id, $mno, $location, $cause, $hours, $current_time);
                                    echo "Exposure report through android app was successfull" . "</br>";
                                }
                            }
                        }
                    }
                }

//update tbl_logs_inbox sms_status to 2 to stop loops
                $this->update_inbox($new_level, $mno);
            }
        }
    }

//    function broadcast() {
//
//
//        $broad = $this->db->query("SELECT id,approval_status,date_created,county_id,sub_county_id,facility_id,msg,cadre_id,sms_status,sms_datetime FROM tbl_sms_broadcast WHERE sms_status='1'");
//        
//        if ($broad->num_rows() > 0) {
//           
//            $sms = $broad->result();
//            foreach ($sms as $x) {
//                
//                $county_id = $x->county_id;
//                $msg_id = $x->id;
//                $sub_county_id = $x->sub_county_id;
//                $facility_id = $x->facility_id;
//                $cadre_id = $x->cadre_id;
//                $approval_status = $x->approval_status;
//                $send_date = $x->date_created;
//
//                $current_date = date("Y-m-d");
//                $a_explode = explode(" ", $send_date);
//                $today = $a_explode[0];
//
//
//
//                if ($approval_status == 'Approved' && $current_date == $today) {
//
//                    $list = $this->db->query("SELECT  county_id,sub_county_id,facility_id,cadre_id,mobile_no FROM  tbl_patientdetails 
//                        INNER JOIN
//                        tbl_master_facility ON tbl_master_facility.code = tbl_patientdetails.facility_id
//                        INNER JOIN
//                        tbl_cadre ON tbl_cadre.id = tbl_patientdetails.cadre_id WHERE
//                        county_id = '$county_id' OR sub_county_id = '$sub_county_id' OR cadre_id = '$cadre_id' 
//                        OR facility_id = '$facility_id'");
//                    if ($list->num_rows() > 0) {
//                        $sms_list = $list->result();
//                        foreach ($sms_list as $x) {
//                            $mno = $x->mobile_no;
//                            $this->insert_tbl_logs_broadcast($mno, $msg_id);
//                        }
//                    }
//                }
//            }
//        }
//        
//         
//    }

    function broadcast() {
        $broad = $this->db->query("SELECT approval_status,id,date_created,county_id,sub_county_id,facility_id,msg,cadre_id,sms_status,sms_datetime FROM tbl_sms_broadcast WHERE sms_status='3'");
        if ($broad->num_rows() > 0) {
            $sms = $broad->result();
            foreach ($sms as $x) {
                $county_id = $x->county_id;
                $msg_id = $x->id;
                $sub_county_id = $x->sub_county_id;
                $facility_id = $x->facility_id;
                $cadre_id = $x->cadre_id;
                $send_date = $x->date_created;
                $approval_status = $x->approval_status;

                $current_date = date("Y-m-d");
                $a_explode = explode(" ", $send_date);
                $today = $a_explode[0];

                $this->b_data($county_id, $msg_id, $sub_county_id, $facility_id, $cadre_id);
            }
            if ($current_date === $today) {
                //$this->b_data($county_id, $msg_id, $sub_county_id, $facility_id, $cadre_id);
            }
        }
    }

    function b_data($county_id, $msg_id, $sub_county_id, $facility_id, $cadre_id) {

        $this->db->select('mobile_no');
        $this->db->from('tbl_patientdetails');
        $this->db->join('master_facility', 'patientdetails.facility_id=master_facility.code ');


        if (!empty($cadre_id)) {
            if ($cadre_id == 0) {
                
            } else {
                $this->db->join('cadre', 'cadre.id = tbl_patientdetails.cadre_id');
                $this->db->where('cadre_id', $cadre_id);
            }
        }
        if (!empty($facility_id)) {

            $this->db->where('patientdetails.facility_id', $facility_id);
        }
        if (!empty($county_id)) {
            if ($county_id == 0) {
                
            } else {
                $this->db->join('county', 'county.id=master_facility.county_id');
                $this->db->where('master_facility.county_id', $county_id);
            }
        }
        if (!empty($sub_county_id)) {

            $this->db->join('sub_county', 'sub_county.id = master_facility.Sub_County_ID');
            $this->db->where('sub_county.id', $sub_county_id);
        }
        $sql = $this->db->get()->result();
        foreach ($sql as $x) {
            $mno = $x->mobile_no;
            $this->insert_tbl_logs_broadcast($mno, $msg_id);
            //$sql = $this->db->query($query)->result_array();                        
        }
        return $sql;
    }

    function insert_tbl_logs_broadcast($mno, $msg_id) {
        echo $mno . "  " . $msg_id . "<br>";
        //$query = $this->db->query("INSERT INTO tbl_logs_broadcast (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        //$this->broadcast_sender();
    }

    function broadcast_sender() {
        $query = $this->db->query("SELECT id,message_id,mobile_no,p_level FROM tbl_logs_broadcast where status='1'")->result();
        foreach ($query as $value) {
            $id = $value->id;
            $messages_id = $value->message_id;
            $mobile_no = $value->mobile_no;


            $query_2 = $this->db->query("SELECT msg,id FROM tbl_sms_broadcast WHERE id='$messages_id'")->result();

            foreach ($query_2 as $value) {
                $message = $value->msg;

//$query_4 = $this->db->query("SELECT * FROM tbl_clients WHERE facility_id = '$code'")->result();


                $senderid = "40149";

                $mobile = substr($mobile_no, -9);
                $len = strlen($mobile);
                if ($len < 10) {
                    $to = "254" . $mobile;
                }

                if ($to <> '') {
                    $from = $senderid;
//$id = $id;
                    $text = $message;
                    $notifyUrl = "197.248.10.20/infobip/notify.php";
                    $notifyContentType = "application/json";
                    $callbackData = "1";
                    $username = "hdindi";
                    $password = "Harr1s123!@#";
                    $postUrl = "https://api.infobip.com/sms/1/text/advanced";
// creating an object for sending SMS
                    $destination = array("to" => $to);
                    $message = array("from" => $from,
                        "destinations" => array($destination),
                        "text" => $text,
                        "notifyUrl" => $notifyUrl,
                        "notifyContentType" => $notifyContentType,
                        "callbackData" => $callbackData);
                    $postData = array("messages" => array($message));
                    $postDataJson = json_encode($postData);
                    $ch = curl_init();
                    $header = array("Content-Type:application/json", "Accept:application/json");
                    curl_setopt($ch, CURLOPT_URL, $postUrl);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// response of the POST request
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $responseBody = json_decode($response);
                    curl_close($ch);
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $messages = $responseBody->messages;
                        echo '<h4>Response</h4><br>';
                        ?>

                        <!--        End Infobip API-->


                        <div>
                            <table id="logs_table" class="table table-condensed">
                                <thead>
                                    <tr>
                                    <th>Message ID</th>
                                    <th>To</th>
                                    <th>Status Group ID</th>
                                    <th>Status Group Name</th>
                                    <th>Status ID</th>
                                    <th>Status Name</th>
                                    <th>Status Description</th>
                                    <th>SMS Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($messages as $message) {
                                        echo "<tr>";
                                        echo "<td>" . $message->messageId . "</td>";
                                        echo "<td>" . $message->to . "</td>";
                                        echo "<td>" . $message->status->groupId . "</td>";
                                        echo "<td>" . $message->status->groupName . "</td>";
                                        echo "<td>" . $message->status->id . "</td>";
                                        echo "<td>" . $message->status->name . "</td>";
                                        echo "<td>" . $message->status->description . "</td>";
                                        echo "<td>" . $message->smsCount . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <b>An error occurred!</b> Reason: Phone number is missing
                    </div>
                    <?php
                }
//update status to sent to stop loops
                $status = '2';
                $query_update = $this->db->query("UPDATE tbl_logs_broadcast SET STATUS ='$status' where mobile_no='$to' ;");
                $query_update = $this->db->query("UPDATE tbl_sms_broadcast SET sms_STATUS ='$status';");
            }


//                $query_3 = $this->db->query("SELECT * FROM tbl_users WHERE facility_id = '$code'")->result();
        }
    }

    function insert_or_update_tblpatientdetails($new_level, $text, $level, $mno) {
        $pick_Level = $this->getLevel($mno, $level);
        foreach ($pick_Level as $value) {
            $hc_level = $value->level;
            $msg_id = $hc_level + 1;
            $report = $msg_id + 1;
            $id = $value->id;
//Current date
            $current_time = date("Y-m-d");
//Explode text message from inbox
            $msg = explode(" ", $text);
            $count_msg = count($msg);

            // echo 'HCW level = ' . $hc_level . '</br> ';

            if ($hc_level == 1) {

                $f_name = "";
                $l_name = "";
                $national_id = "";

                if ($count_msg == 3) {

                    $f_name = $msg[0];
                    $l_name = $msg[1];
                    $national_id = $msg[2];

                    echo "First Name = " . $f_name . "</br>";
                    echo "Lsst Name = " . $l_name . "</br>";
                    echo "National ID = " . $national_id . "</br>";

                    if (is_string($f_name) && is_string($l_name) && ctype_digit($national_id) && ($national_id > 0 || $national_id <= 30)) {
                        $this->update_patientdetails($f_name, $l_name, $national_id, $msg_id, $mno);
                        echo 'updated tbl_patientdetails -name and id ' . '</br>';
                    } else {
                        echo 'Invalid Input hcw has swapped input fields' . '</br>';
                        $msg_id = 61;
                        $this->insert_outbox($mno, $msg_id);
                    }
                } else {
                    $msg_id = 61;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
            if ($hc_level == 2) {

                $cadre = "";
                $facility_id = "";
                $facility_length = "";

                if ($count_msg == 2) {
                    $cadre = $msg[0];
                    $facility_id = $msg[1];
                    if (ctype_digit($cadre) && ($cadre > 0 && $cadre <= 9 ) && ctype_digit($facility_id) && (strlen($facility_id) == '5')) {
                        $query_mfl = $this->db->query("SELECT CODE FROM tbl_master_facility where code='$facility_id'");
                        if ($query_mfl->num_rows() > 0) {
                            $this->update_patientdetails_insert_cadre($msg_id, $mno, $cadre, $facility_id);
                            echo "Cadre = " . $cadre . "</br>";
                            echo "MFL NO. = " . $facility_id . "</br>";
                        } else {
//MFL does not exist
                            $msg_id = 67;
                            $this->insert_outbox($mno, $msg_id);
                        }
                    } else {
                        $msg_id = 62;
                        $this->insert_outbox($mno, $msg_id);
                    }
                } else {
                    $msg_id = 62;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
            if ($hc_level == 3) {

                $gender = "";
                $yob = "";
                $age = "";

                if ($count_msg == 2) {

                    $gender = $msg[0];
                    $yob = $msg[1];
//check if one is 18 yars old.
                    if (ctype_digit($gender) && ($gender == 1 || $gender == 2) && (ctype_digit($yob) && ($yob > 1937 || $yob < 1999 ))) {
                        $this->update_patientdetails_insert_gender($msg_id, $mno, $gender, $yob);
                        echo "Gender = " . $gender . "</br>";
                        echo "YOB = " . $yob . "</br>";
                    } else {
                        $msg_id = 63;
                        $this->insert_outbox($mno, $msg_id);
                    }
                } else {
                    echo'Invalid input for gender and YOB';
                    $msg_id = 63;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
            if ($hc_level == 4) {
                $hep_response = "";
                if ($count_msg == 1) {
                    $hep_response = $msg[0];
                    if (ctype_digit($hep_response) && ($hep_response > 0 && $hep_response <= 3)) {
                        $this->hepatitis_B($mno, $msg_id, $hep_response);
                        echo 'Inserrted location = ' . $hep_response . "</br>";
                    } else {
                        $msg_id = 57;
                        $this->insert_outbox($mno, $msg_id);
                        echo 'User inserted a number grater than 9';
                    }
                } else {
                    $msg_id = 57;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
//            if ($hc_level == 5) {
//                $this->prompt_one_to_report($msg_id, $mno);
//            }
            if ($hc_level == 6) {
                $r_response = "";
                if ($count_msg == 1) {
                    $r_response = $msg[0];
                    switch ($r_response) {
                        case 1:
                            $this->start_report($msg_id, $mno);
                            break;
                        case 2:
                            $this->technical_support($mno);
                            break;
                        default:
                            $msg_id = 6;
                            $this->insert_outbox($mno, $msg_id);
                            break;
                    }
                } else {
                    $msg_id = 6;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
            if ($hc_level == 7) {
                $e_type = "";
                $no_of_hours = "";
                // $re_ex_count = "";

                if ($count_msg == 2) {

                    $e_type = $msg[0];
                    $no_of_hours = $msg[1];

                    if (ctype_digit($e_type) && ($e_type > 0 && $e_type <= 6 ) && strlen($e_type) == 1 && ($no_of_hours > 0 && ctype_digit($no_of_hours))) {

                        if ($this->check_re_exposure($id) == "yes") {

                            $query_chk = $this->db->query("SELECT re_exposure_count FROM tbl_reports where p_details_id='$id'");
                            if ($query_chk->num_rows() > 0) {
                                $check = $query_chk->result();
                                $re_ex_count = "";
                                foreach ($check as $x) {
                                    $count = $x->re_exposure_count;
                                    $re_ex_count = $count + 1;

                                    $this->update_exposure_type($re_ex_count, $mno, $msg_id, $id, $e_type, $no_of_hours);
                                }
                            }
                        } elseif ($this->check_re_exposure($id) == "no") {
                            $this->report_exposure_type($mno, $msg_id, $id, $e_type, $no_of_hours);
                        }
                    } else {
                        $msg_id = 65;
                        $this->insert_outbox($mno, $msg_id);
                    }
                } else {
                    $msg_id = 65;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
            if ($hc_level == 8) {
                $e_location = "";
                if ($count_msg == 1) {
                    $e_location = $msg[0];
                    if (ctype_digit($e_location) && ($e_location > 0 && $e_location <= 9)) {
                        $this->report_location($report, $mno, $msg_id, $id, $e_location, $hc_level);
                        echo 'Inserrted location = ' . $e_location . "</br>";
                    } else {
                        $msg_id = 66;
                        $this->insert_outbox($mno, $msg_id);
                        echo 'User inserted a number grater than 9';
                    }
                } else {
                    $msg_id = 66;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
        }
    }

    function report_exposure_type($mno, $msg_id, $id, $e_type, $no_of_hours) {
        $this->insert_outbox($mno, $msg_id);
        $date = date("Y-m-d H:i:s");
        $query = $this->db->query("INSERT INTO tbl_reports(p_details_id,exposure_hours,exposure_type,date_exposed) VALUES ('$id','$no_of_hours','$e_type','$date')");
        $query = $this->db->query("update tbl_patientdetails SET level='$msg_id' where mobile_no='$mno'");
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function update_exposure_type($re_ex_count, $mno, $msg_id, $id, $e_type, $no_of_hours) {
        $this->insert_outbox($mno, $msg_id);
        $date = date("Y-m-d H:i:s");
        $query = $this->db->query("update tbl_reports set re_exposure_count='$re_ex_count', exposure_hours='$no_of_hours',exposure_type='$e_type',date_exposed='$date' where p_details_id='$id'");
        $query = $this->db->query("update tbl_patientdetails SET level='$msg_id' where mobile_no='$mno'");
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function report_location($report, $mno, $msg_id, $id, $e_location, $hc_level) {

        $date = date("Y-m-d H:i:s");
        $query = $this->db->query("update  tbl_reports set date_exposed='$date', exposure_location='$e_location' where p_details_id='$id'");
        $query = $this->db->query("update tbl_patientdetails SET level='$msg_id' where mobile_no='$mno'");
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
        //re-exposure insertion
        $this->report_all_re_exposure($id);

        $query_chk = $this->db->query("SELECT re_exposure_count,exposure_hours FROM tbl_reports where p_details_id='$id'");
        if ($query_chk->num_rows() > 0) {
            $check = $query_chk->result();
            $re_ex_count = "";
            $ex_hours = "";
            foreach ($check as $x) {
                $re_ex_count = $x->re_exposure_count;
                $ex_hours = $x->exposure_hours;
                if ($re_ex_count >= 1) {
                    $this->re_exposureMsg0ne($mno, $msg_id);
                } else {
                    $this->insert_outbox($mno, $msg_id);
                }
                if ($ex_hours >= 72) {
                    $msg_id = 71;
                    $this->insert_outbox($mno, $msg_id);
                }
            }
        }
    }

    function above_72hours($mno, $msg_id) {
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->sender();
    }

//Re-exposure report
    function android_reexposure($hcw_p_id, $mno, $location, $cause, $hours, $re_ex_count, $count, $current_time) {
        $r_text = 9;
        $query = $this->db->query("update tbl_reports set exposure_location='$location',exposure_type='$cause',exposure_hours='$hours' ,re_exposure_count='$re_ex_count',date_exposed='$current_time' where p_details_id='$hcw_p_id'");
        $query = $this->db->query("update tbl_logs_inbox SET level='$r_text' where mobile_no='$mno'");
        $query = $this->db->query("update tbl_patientdetails SET level='$r_text' where mobile_no='$mno'");

        if ($hours >= 72) {
            $text = 71;
            $this->android_complete_report($mno, $text);
        }
        if ($count >= 1) {
            $text = 89;
            $this->android_complete_report($mno, $text);
//            $text = 90;
//            $this->android_complete_report($mno, $text);
        }
    }

    function android_complete_report($mno, $text) {
// $complete_report = 8;
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$text','$mno')");

        $this->sender();
    }

//First Time report
    function android_report($hcw_p_id, $mno, $location, $cause, $hours, $current_time) {
        $r_text = 9;
        $query = $this->db->query("INSERT INTO tbl_reports  (exposure_location,exposure_type,exposure_hours,p_details_id,date_exposed) VALUES ('$location','$cause','$hours','$hcw_p_id','$current_time')");
        $query = $this->db->query("update tbl_patientdetails SET level='$r_text' where mobile_no='$mno'");
//$query_update = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->android_complete_report($mno, $r_text);
        $query = $this->db->query("update tbl_logs_inbox SET level='$r_text' where mobile_no='$mno'");

        if ($hours >= 72) {
            $text = 71;
            $this->android_complete_report($mno, $text);
        } else if ($hours < 72) {
            $text = 9;
            $this->android_complete_report($mno, $text);
        }
    }

    function update_inbox($new_level, $mno) {
        $query = $this->db->query("update tbl_logs_inbox SET sms_status='2',level='$new_level' where mobile_no='$mno'");
    }

    function android_exposure_report($hcw_p_id, $mno, $location, $cause, $hours, $current_time) {

        $query = $this->db->query("INSERT INTO tbl_reports_all  (exposure_location,exposure_type,exposure_hours,p_details_id,date_exposed) VALUES ('$location','$cause','$hours','$hcw_p_id','$current_time')");
    }

    function check_responses($hcw_id) {
        $querycheck = $this->db->query("SELECT response,p_details_id FROM tbl_reports where p_details_id='$hcw_id'");
        if ($querycheck->num_rows() > 0) {
            $response = $querycheck->result();
            return $response;
        } elseif ($querycheck->num_rows() <= 0) {
            $response = $querycheck->result();
            return $response;
        }
    }

    function check_if_exposed_earlier($hcw_p_id) {
        $querycheck = $this->db->query("SELECT id,p_details_id FROM tbl_reports where p_details_id='$hcw_p_id'");
        if ($querycheck->num_rows() > 0) {
            return "Yes";
        } elseif ($querycheck->num_rows() <= 0) {
            return "No";
        }
    }

    function check_if_mobile_exists($mno) {
        $querycheck = $this->db->query("SELECT mobile_no,id FROM tbl_patientdetails where mobile_no='$mno'");
        if ($querycheck->num_rows() > 0) {
            return "exists";
        } elseif ($querycheck->num_rows() <= 0) {
            return "empty";
        }
    }

    function messages_adherence($text, $adherence_level, $mob_no, $hcw_id) {
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$text','$mob_no')");
//$query = $this->db->query("update tbl_reports set adherence_level='$adherence_level' where p_details_id='$hcw_id'");
        $this->update_adherence_level($hcw_id, $adherence_level);
        $this->sender();
    }

    function messages_responses($text, $mob_no, $new_adherence, $hcw_id) {
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$text','$mob_no')");

        $this->sender();
        $this->update_res_level($new_adherence, $hcw_id);
    }

    function update_adherence_level($hcw_id, $adherence_level) {
        $query = $this->db->query("update tbl_reports set adherence_level='$adherence_level' where p_details_id='$hcw_id'");
    }

    function update_res_level($new_adherence, $hcw_id) {
        $query = $this->db->query("update  tbl_reports set response='$new_adherence' where p_details_id='$hcw_id'");
    }

    function insert_to_outbox($mno, $msg_id) {

        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");

        $this->sender();
    }

    function insert_outbox_not_registered($mno, $new_level) {
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$new_level','$mno')");

        $this->sender();
    }

    function insert_outbox($mno, $msg_id) {
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->sender();
    }

    function confirmatory_message_outbox($mno, $msg_id) {
//Add 1 to send a thank you message on complete registration
        $complete_registration = $msg_id + 1;
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");

        $this->sender();
    }

    function android_confirmatory_message_outbox($mno) {
        $complete_registration = 5;
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$complete_registration','$mno')");

        $this->sender();
    }

    function android_confirmatory_two_message_outbox($mno, $msg_id) {
//Add 1 to send a thank you message on complete registration.-C4C provides health care workers with information on occupational PEP and other health preventive and promotion services.<MOH>
        $complete_registration = 5;
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$complete_registration','$mno')");

        $this->sender();
    }

    function update_patientdetails($f_name, $l_name, $national_id, $msg_id, $mno) {
        $query = $this->db->query("update tbl_patientdetails SET f_name='$f_name',l_name='$l_name',national_id='$national_id',level='$msg_id' where mobile_no='$mno'");
//$query_update = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function android_register($mno, $cadre, $national_id, $l_name, $f_name, $gender, $yob, $facility_id, $hepatitis_b, $u_name, $password) {
        $default_level = 5;
        $query = $this->db->query("insert into  tbl_patientdetails (f_name,l_name,DOB,national_id,gender_id,cadre_id,facility_id,username,password,level,mobile_no,hepatitis_b)
            VALUES ('$f_name','$l_name','$yob','$national_id','$gender','$cadre','$facility_id','$u_name','$password','$default_level','$mno','$hepatitis_b')");

        $this->android_confirmatory_message_outbox($mno);
//update inbox level
        $msg_id = 5;
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function android_register_update($mno, $cadre, $national_id, $l_name, $f_name, $gender, $yob, $facility_id, $hepatitis_b, $u_name, $password) {
        $default_level = 5;
        $query = $this->db->query("update  tbl_patientdetails set f_name='$f_name',l_name='$l_name',DOB='$yob',national_id='$national_id',
                cadre_id='$cadre',gender_id='$gender',facility_id='$facility_id',level='$default_level',hepatitis_b='$hepatitis_b',username='$u_name',password='$password' where mobile_no='$mno'");

        $this->android_confirmatory_message_outbox($mno);
//update inbox level
        $msg_id = 4;
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function update_patientdetails_insert_cadre($msg_id, $mno, $cadre, $facility_id) {
        $query = $this->db->query("update tbl_patientdetails SET cadre_id='$cadre',facility_id='$facility_id',level='$msg_id' where mobile_no='$mno'");
//$query_update = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function update_patientdetails_insert_gender($msg_id, $mno, $gender, $yob) {
        $query = $this->db->query("update tbl_patientdetails SET gender_id='$gender',DOB='$yob',level='$msg_id' where mobile_no='$mno'");
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function hepatitis_B($mno, $msg_id, $hep_response) {
        $query = $this->db->query("update tbl_patientdetails SET hepatitis_b='$hep_response',level='$msg_id' where mobile_no='$mno'");
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function prompt_one_to_report($msg_id, $mno) {
//Update level by one to allow continuation aftre full registration
        $query = $this->db->query("update tbl_patientdetails SET level='$msg_id' where mobile_no='$mno'");
//Call confirmatory_message_outbox fn to skip message number 5 from being sent agin since it is sent pon full registration
        $this->confirmatory_message_outbox($mno, $msg_id);
    }

    function start_report($msg_id, $mno) {
        $query = $this->db->query("update tbl_patientdetails SET level='$msg_id' where mobile_no='$mno'");
//$query_update = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function technical_support($mno) {
//sent when user selecets option 2
        $msg_id = 64;
        $this->insert_outbox($mno, $msg_id);
        $query = $this->db->query("update tbl_logs_inbox SET level='$msg_id' where mobile_no='$mno'");
    }

    function re_exposureMsg0ne($mno, $msg_id) {
        $msg_id = 89;
        $this->insert_outbox($mno, $msg_id);
        $this->re_exposureMsgTwo($mno, $msg_id);
    }

    function re_exposureMsgTwo($mno, $msg_id) {
        $msg_id = 90;
        $this->insert_outbox($mno, $msg_id);
    }

    function report_all_re_exposure($id) {
        $tbl_reports_all = $this->db->query("select p_details_id,exposure_hours,exposure_type,exposure_location,adherence_level,date_exposed from tbl_reports where p_details_id='$id'");
        if ($tbl_reports_all->num_rows() > 0) {
            $tbl_reports_all = $tbl_reports_all->result();
            $hcwid = '';
            $e_hours = '';
            $e_type = '';
            $e_type = '';
            $ef_location = '';
            $d_exposed = '';
            foreach ($tbl_reports_all as $key) {
                $hcwid .= $key->p_details_id;
                $e_hours .= $key->exposure_hours;
                $e_type .= $key->exposure_type;
                $ef_location .= $key->exposure_location;

                $d_exposed .= $key->date_exposed;
                $query = $this->db->query("INSERT INTO tbl_reports_all(p_details_id,exposure_hours,exposure_type,date_exposed,exposure_location) VALUES ('$hcwid','$e_hours','$e_type','$d_exposed','$ef_location')");
            }
        }
    }

    function select_from_reports($id) {
//select from tbl_reports

        $tbl_reports_all = $this->db->query("select p_details_id,exposure_hours,exposure_type,exposure_location,adherence_level,date_exposed from tbl_reports where p_details_id='$id'");
        if ($tbl_reports_all->num_rows() > 0) {
            foreach ($tbl_reports_all as $key) {
                $hcwid = $key->p_details_id;
                $e_hours = $key->exposure_hours;
                $e_type = $key->exposure_type;
                $ef_location = $key->exposure_location;
                $a_level = $key->adherence_level;
                $d_exposed = $key->date_exposed;

                $this->report_location_all($hcwid, $e_hours, $e_type, $ef_location, $d_exposed);
            }
        }
    }

    function report_location_all($hcwid, $e_hours, $e_type, $ef_location, $d_exposed) {
        $query = $this->db->query("INSERT INTO tbl_reports_all(p_details_id,exposure_hours,exposure_type,date_exposed) VALUES ('$hcwid','$e_hours','$e_type','$d_exposed')");
    }

    function update_level_patientdetails($mno, $new_level) {
        $query = $this->db->query("update  tbl_patientdetails set level='$new_level' where mobile_no='$mno'");
    }

    function insert_patientdetails_insert_number($mno, $new_level) {
        $query = $this->db->query("insert INTO tbl_patientdetails (mobile_no,level) VALUES ('$mno','$new_level')");
    }

    function run() {
        $query_a = $this->db->query("SELECT * FROM tbl_patientdetails_copy ORDER BY id DESC ");
        if ($query_a->num_rows() > 0) {
            $exposure_hours = $query_a->result();
            foreach ($exposure_hours as $e_hours) {
                $id = $e_hours->id;
                $mno = $e_hours->mobile_no;
                //echo $DOB;
//                $ab_explode = explode(" ", $broadcast_date);
//                $broadcastdate = str_replace('/', '-', $ab_explode[0]);
//                $a_explode = explode("-", $broadcastdate);
//                $mn = $a_explode[0];
//                $day = $a_explode[1];
//                $yr = $a_explode[2];
//                $nwe_date = $yr . "-" . $mn . "-" . $day;
                $mobile = substr($mno, -9);
                $len = strlen($mobile);

                if ($len < 10) {
                    $to = "254" . $mobile;
                }
                echo $to;
                //$broadcast_date = date("Y-m-d", strtotime($broadcastdate));
                $this->db->trans_start();

                $query = $this->db->query("update tbl_patientdetails_copy SET mobile_no='$to' where id='$id' ");

                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    //Throw an error
                    echo 'Error Occured...';
                } else {
                    echo 'Success update ....';
                }
            }
        }
    }

    function sender() {
        $query = $this->db->query("SELECT id,message_id,mobile_no,p_level,STATUS FROM tbl_logs_outbox WHERE STATUS='1' ORDER BY id DESC")->result();
        foreach ($query as $value) {
            $id = $value->id;
            $messages_id = $value->message_id;
            $mobile_no = $value->mobile_no;

//insert HCW name into text
            $query_msgID = $this->db->query("SELECT messages,id FROM tbl_messages WHERE id='$messages_id'")->result();
            foreach ($query_msgID as $msg_ID) {
                $text = $msg_ID->messages;
                $Hello = strpos($text, 'Hello', 0);
                $welcome = strpos($text, 'Welcome', 0);

                if ($Hello !== false) {
                    $query_name = $this->db->query("SELECT f_name,mobile_no FROM tbl_patientdetails  WHERE mobile_no='$mobile_no'")->result();
                    foreach ($query_name as $fnm) {
                        $name = $fnm->f_name;
//substr_replace(string,replacement,start,length)
                        $message = substr_replace($text, $name, 6, 0);
                        //echo "Text -" . $message;
                    }
                } else if ($welcome !== false) {
                    $query_name = $this->db->query("SELECT f_name,mobile_no FROM tbl_patientdetails  WHERE mobile_no='$mobile_no'")->result();
                    foreach ($query_name as $nm) {
                        $fname = $nm->f_name;
//substr_replace(string,replacement,start,length)
                        $message = substr_replace($text, $fname, 8, 0);
                    }
                } else {

                    $message = $text;
                }


                $senderid = "40149";
                $mobile = substr($mobile_no, -9);
                $len = strlen($mobile);



                if ($len < 10) {
                    $to = "254" . $mobile;
                }

                if ($to <> '') {
                    $from = $senderid;
//$id = $id;
                    $text = $message;
                    $notifyUrl = "197.248.10.20/infobip/notify.php";
                    $notifyContentType = "application/json";
                    $callbackData = "1";
                    $username = "hdindi";
                    $password = "Harr1s123!@#";
                    $postUrl = "https://api.infobip.com/sms/1/text/advanced";
// creating an object for sending SMS
                    $destination = array("to" => $to);
                    $message = array("from" => $from,
                        "destinations" => array($destination),
                        "text" => $text,
                        "notifyUrl" => $notifyUrl,
                        "notifyContentType" => $notifyContentType,
                        "callbackData" => $callbackData);
                    $postData = array("messages" => array($message));
                    $postDataJson = json_encode($postData);
                    $ch = curl_init();
                    $header = array("Content-Type:application/json", "Accept:application/json");
                    curl_setopt($ch, CURLOPT_URL, $postUrl);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// response of the POST request
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $responseBody = json_decode($response);
                    curl_close($ch);
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $messages = $responseBody->messages;
                        // echo '<h4>Response</h4><br>';//                        
                    }
                } else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <b>An error occurred!</b> Reason: Phone number is missing
                    </div>
                    <?php
                }
            }
        }
//update status to 2(sent) to avoid loops.
        $status = '2';
        $query_update = $this->db->query("UPDATE tbl_logs_outbox SET STATUS ='$status' where mobile_no='$to';");
    }

    function confirmatory_message() {
        $query_outbox = $this->db->query("SELECT mobile_no,message_id,date_created,mobile_no FROM tbl_logs_outbox WHERE message_id=5");
        if ($query_outbox->num_rows() > 0) {
            $minutes = $query_outbox->result();
            foreach ($minutes as $m) {
                $date = $m->date_created;
                $mno = $m->mobile_no;
                $date_sent = strtotime($date);
                $datetime = strtotime("now");

                $substract = $datetime - $date_sent;
                $r_minutes = $substract / 60;
                $minutes = round($r_minutes);
                //echo 'This=>'." ".$minutes."  ".$mobile_no;

                if ($minutes < 5) {
                    $this->confirm_registration_text($mno);
                }
            }
        }
    }

    function confirm_registration_text($mno) {
        $msg_id = 31;
        $new_level = 6;
        $this->update_inbox($new_level, $mno);
        $this->insert_outbox($mno, $msg_id);
        $this->update_level_patientdetails($mno, $new_level);
    }

    function getLevel($mno, $level) {
        $queryone = $this->db->query("SELECT level,id,mobile_no FROM tbl_patientdetails where mobile_no='$mno'");
        if ($queryone->num_rows() > 0) {
            $hcw_level = $queryone->result();
            return $hcw_level;
        } else if ($queryone->num_rows() <= 0) {
            return "HCW level was not found";
//echo 'nothing found';
        }
    }

    function check_re_exposure($id) {
        $q_check = $this->db->query("SELECT p_details_id FROM tbl_reports where p_details_id='$id'");
        if ($q_check->num_rows() > 0) {
            return "yes";
        } else if ($q_check->num_rows() <= 0) {
            return "no";
        }
    }

    function is_exposed($id) {
        $q_check = $this->db->query("SELECT p_details_id,exposure_hours,date_exposed FROM tbl_reports where p_details_id='$id'");
        if ($q_check->num_rows() > 0) {
            $yes_exposed = $q_check->result();
            return $yes_exposed;
        } else if ($q_check->num_rows() <= 0) {
            return "Not Found";
//echo 'nothing found';
        }
    }

    //send broadcast messages every 14 days
    function automated_broadcast() {
        $querytwo = $this->db->query("SELECT id,messages FROM tbl_messages WHERE category_id='6'");
        if ($querytwo->num_rows() > 0) {
            $query_hjw = $querytwo->result();
            foreach ($query_hjw as $m) {
                $mid = $m->id;
                $query_p = $this->db->query("SELECT mobile_no FROM tbl_patientdetails LEFT JOIN tbl_reports ON tbl_reports.p_details_id=tbl_patientdetails.id");
                if ($query_p->num_rows() > 0) {
                    $query_hww = $query_p->result();
                    foreach ($query_hww as $rep_hcw) {
                        $mno = $rep_hcw->mobile_no;
                        $query_outbox = $this->db->query("SELECT id,mobile_no,message_id,date_created FROM tbl_logs_outbox WHERE message_id='$mid' and mobile_no='$mno'");
                        if ($query_outbox->num_rows() > 0) {
                            $data = $query_outbox->result();
                            foreach ($data as $o_days) {
                                $text_id = $o_days->message_id;
                                $r_date = $o_days->date_created;

                                $current_time = date("Y-m-d H:i:s");
                                $datetime = strtotime("now");
                                $report_date = strtotime($r_date);
                                $no_after = $datetime - $report_date;
                                $ddays = $no_after / 86400;
                                $r_days = round($ddays);
                                $hours = $no_after / 3600;
                            }

                            if ($r_days == 1 && $text_id != 111) {
                                $msg_id = $text_id + 1;
                                //echo "This =  " . " " . $mno . " " . $msg_id . "</br>" . "</br>";
                                $this->insert_autobroadcast($mno, $msg_id);
                            }
                            if ($r_days == 1 && $text_id == 111) {
                                $msg_id = $text_id - 19;
                                //echo "This =  " . " " . $mno . " " . $msg_id . "</br>" . "</br>";
                                $this->insert_autobroadcast($mno, $msg_id);
                            }
                        }if ($query_outbox->num_rows() == 0) {
                            if ($mid == 93) {
                                $msg_id = 93;
                                //echo "This =  " . " " . $mno . " " . $msg_id . "</br>" . "</br>";
                                $this->insert_autobroadcast($mno, $msg_id);
                            }
                        }
                    }
                }
            }
        }
    }

    function insert_autobroadcast($mno, $msg_id) {
        //echo "This =  " . " " . $mno . " " . $msg_id . "</br>" . "</br>";
        $query = $this->db->query("INSERT INTO tbl_logs_outbox (message_id,mobile_no) VALUES ('$msg_id','$mno')");
        //$this->auto_broadcast_sender();
    }

    function auto_broadcast_sender() {
        $query = $this->db->query("SELECT id,message_id,mobile_no,p_level,STATUS FROM tbl_logs_outbox WHERE STATUS='1' ORDER BY id DESC LIMIT 30")->result();
        foreach ($query as $value) {
            $id = $value->id;
            $messages_id = $value->message_id;
            $mobile_no = $value->mobile_no;

//insert HCW name into text
            $query_msgID = $this->db->query("SELECT messages,id FROM tbl_messages WHERE id='$messages_id'")->result();
            foreach ($query_msgID as $msg_ID) {
                $text = $msg_ID->messages;
                $Hello = strpos($text, 'Hello', 0);
                $welcome = strpos($text, 'Welcome', 0);

                if ($Hello !== false) {
                    $query_name = $this->db->query("SELECT f_name,mobile_no FROM tbl_patientdetails  WHERE mobile_no='$mobile_no'")->result();
                    foreach ($query_name as $fnm) {
                        $name = $fnm->f_name;
//substr_replace(string,replacement,start,length)
                        $message = substr_replace($text, $name, 6, 0);
                        //echo "Text -" . $message;
                    }
                } else if ($welcome !== false) {
                    $query_name = $this->db->query("SELECT f_name,mobile_no FROM tbl_patientdetails  WHERE mobile_no='$mobile_no'")->result();
                    foreach ($query_name as $nm) {
                        $fname = $nm->f_name;
//substr_replace(string,replacement,start,length)
                        $message = substr_replace($text, $fname, 8, 0);
                    }
                } else {

                    $message = $text;
                }


                $senderid = "40149";
                $mobile = substr($mobile_no, -9);
                $len = strlen($mobile);



                if ($len < 10) {
                    $to = "254" . $mobile;
                }

                if ($to <> '') {
                    $from = $senderid;
//$id = $id;
                    $text = $message;
                    $notifyUrl = "197.248.10.20/infobip/notify.php";
                    $notifyContentType = "application/json";
                    $callbackData = "1";
                    $username = "hdindi";
                    $password = "Harr1s123!@#";
                    $postUrl = "https://api.infobip.com/sms/1/text/advanced";
// creating an object for sending SMS
                    $destination = array("to" => $to);
                    $message = array("from" => $from,
                        "destinations" => array($destination),
                        "text" => $text,
                        "notifyUrl" => $notifyUrl,
                        "notifyContentType" => $notifyContentType,
                        "callbackData" => $callbackData);
                    $postData = array("messages" => array($message));
                    $postDataJson = json_encode($postData);
                    $ch = curl_init();
                    $header = array("Content-Type:application/json", "Accept:application/json");
                    curl_setopt($ch, CURLOPT_URL, $postUrl);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// response of the POST request
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $responseBody = json_decode($response);
                    curl_close($ch);
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $messages = $responseBody->messages;
                        // echo '<h4>Response</h4><br>';//                        
                    }
                } else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <b>An error occurred!</b> Reason: Phone number is missing
                    </div>
                    <?php
                }
            }
        }
//update status to 2(sent) to avoid loops.
        $status = '2';
        $query_update = $this->db->query("UPDATE tbl_logs_outbox SET STATUS ='$status' where mobile_no='$to';");
    }

    function responses_to_adherence() {

        $query_a = $this->db->query("SELECT p_details_id ,mobile_no ,exposure_hours,date_exposed FROM `tbl_patientdetails` INNER JOIN tbl_reports ON `tbl_reports`.`p_details_id`=`tbl_patientdetails`.`id`");
        if ($query_a->num_rows() > 0) {
            $exposure_hours = $query_a->result();
            foreach ($exposure_hours as $e_hours) {
                $exp_hours = $e_hours->exposure_hours;
                $r_date = $e_hours->date_exposed;
                $hcw_id = $e_hours->p_details_id;
                $mob_no = $e_hours->mobile_no;
                $current_time = date("Y-m-d H:i:s");
                $datetime = strtotime("now");
                $report_date = strtotime($r_date);
                $no_after = $datetime - $report_date;
                $days = $no_after / 86400;
                $r_days = round($days);
                $hours = $no_after / 3600;
                $total_hours = $hours + $exp_hours;
                $rnd_hours = round($total_hours);
                $r_minutes = $no_after / 60;
                $minutes = round($r_minutes);

                $h_hours = round($total_hours);

// $t_hours = $hours + 6;
//echo $minutes . "</br> ";
                //echo " HCW id = " . $hcw_id . "</br> " . " Current Time= " . $current_time . "</br> " . "Sum of hours after exposure + current time = " . $h_hours . "</br>" . "Hours after exposure on report =" . $exp_hours . "</br> " . "Number of days after exposure =" . $r_days . "</br> " . "Minutes after exposure = " . $minutes . "</br>" . "</br>";
//24 hours

                if ($exp_hours >= 72) {

                    $query_date = $this->db->query("SELECT message_id,mobile_no,date_created FROM tbl_logs_outbox WHERE mobile_no='$mob_no' AND message_id= '71' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_created;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";


                            if ($round_mins < 5) {
                                $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                                if ($query_date->num_rows() > 0) {
                                    $date = $query_date->result();
                                    foreach ($date as $dates) {
                                        $date_rcd = $dates->date_received;
                                        $date_ex = (explode(" ", $date_rcd));
                                        $date_rcvd = $date_ex[0];

                                        $now = date("Y-m-d H:i:s");
                                        $right_now = $now[0];
                                        $date_now = (explode(" ", $right_now));
                                        $sahii = $date_now[0];

                                        $date_created = strtotime($date_rcd);
                                        $minutes_diff = $dfatetime - $date_created;
                                        $minutes_e = $minutes_diff / 60;
                                        $round_mins = round($minutes_e);
                                        echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";


                                        if ($query_date->num_rows() > 0 && $round_mins < 5) {
                                            $text = 16;
                                            $new_adherence = 1;
                                            $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                                        }
                                    }
                                }

                                $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                                if ($query_date->num_rows() > 0) {
                                    $date = $query_date->result();
                                    foreach ($date as $dates) {
                                        $date_rcd = $dates->date_received;
                                        $date_ex = (explode(" ", $date_rcd));
                                        $date_rcvd = $date_ex[0];

                                        $now = date("Y-m-d H:i:s");
                                        $right_now = $now[0];
                                        $date_now = (explode(" ", $right_now));
                                        $sahii = $date_now[0];

                                        $date_created = strtotime($date_rcd);
                                        $minutes_diff = $datetime - $date_created;
                                        $minutes_e = $minutes_diff / 60;
                                        $round_mins = round($minutes_e);
                                        //echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                                        if ($round_mins < 5) {
                                            $text = 14;
                                            $new_adherence = 2;
                                            $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($h_hours == 24) {

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
// echo $minutes . "</br> ";
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            //echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br>" . "</br>";

                            if ($query_date->num_rows() > 0 && $round_mins < 5) {
                                $text = 11;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 12;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 48) {
//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            //echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 11;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";
                            if ($round_mins < 5) {
                                $text = 14;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 60) {


//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 11;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 14;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
// one who responds to 72 hour message-yes response
                if ($exp_hours == 72) {

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 17;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 18;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 96) {



                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 24;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 25;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 216) {



//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 24;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 25;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 720) {
//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 11;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 12;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 744) {

//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";
                            if ($round_mins < 5) {
                                $text = 11;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 12;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 2208) {


//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 24;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 29;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 2256) {


//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 24;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins <= 5) {
                                $text = 29;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
                if ($h_hours == 4368) {


//Handle responses after the hourly message- Yes or No

                    $query_date = $this->db->query("SELECT msg,date_received,msg FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%yes' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message was received in tbl_inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 86;
                                $new_adherence = 1;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }

                    $query_date = $this->db->query("SELECT msg,date_received FROM tbl_logs_inbox WHERE mobile_no='$mob_no' AND msg LIKE '%no' ");
                    if ($query_date->num_rows() > 0) {
                        $date = $query_date->result();
                        foreach ($date as $dates) {
                            $date_rcd = $dates->date_received;
                            $date_ex = (explode(" ", $date_rcd));
                            $date_rcvd = $date_ex[0];

                            $now = date("Y-m-d H:i:s");
                            $right_now = $now[0];
                            $date_now = (explode(" ", $right_now));
                            $sahii = $date_now[0];

                            $date_created = strtotime($date_rcd);
                            $minutes_diff = $datetime - $date_created;
                            $minutes_e = $minutes_diff / 60;
                            $round_mins = round($minutes_e);
                            echo "Minutes after message hit inbox = " . $round_mins . "</br> ";

                            if ($round_mins < 5) {
                                $text = 29;
                                $new_adherence = 2;
                                $this->messages_responses($text, $mob_no, $new_adherence, $hcw_id);
                            }
                        }
                    }
                }
            }
        }
    }

    function adherence() {
        $query_a = $this->db->query("SELECT mobile_no,f_name,p_details_id ,mobile_no ,exposure_hours,date_exposed FROM `tbl_patientdetails` INNER JOIN tbl_reports ON `tbl_reports`.`p_details_id`=`tbl_patientdetails`.`id`");
        $exposure_hours = $query_a->result();
        foreach ($exposure_hours as $e_hours) {
            $exp_hours = $e_hours->exposure_hours;
            $r_date = $e_hours->date_exposed;
            $hcw_id = $e_hours->p_details_id;
            $mob_no = $e_hours->mobile_no;
            $name = $e_hours->f_name;
            $number = $e_hours->mobile_no;
            $current_time = date("Y-m-d H:i:s");
            $datetime = strtotime("now");
            $report_date = strtotime($r_date);
            $no_after = $datetime - $report_date;
            $ddays = $no_after / 86400;
            $r_days = round($ddays);
            $hours = $no_after / 3600;
            $total_hours = $hours + $exp_hours;
            $rnd_hours = round($total_hours);
            $r_minutes = $total_hours * 60;
            $minutes = round($r_minutes);
            $h_hours = round($total_hours);


            //echo "ID = " . $hcw_id . "</br> " . " Mobile Number = " . $number . "</br>" . " Date Exposed= " . $r_date . "</br> " . "Sum of hours after exposure + current time = " . $h_hours . "</br>" . "Hours after exposure on report =" . $exp_hours . "</br> " . "Number of days after exposure =" . $r_days . "</br> " . "Minutes after exposure = " . $minutes . "</br>" . "</br>";

            if ($minutes == 1440) {
                $text = 10;
                $adherence_level = 1;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 2880 && $exp_hours > 24) {
                $text = 13;
                $adherence_level = 2;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 3600 && $exp_hours > 48) {
                $text = 15;
                $adherence_level = 3;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 5760) {
                $text = 28;
                $adherence_level = 5;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 7200) {
                $text = 20;
                $adherence_level = 7;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 7560) {
                $text = 21;
                $adherence_level = 8;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 10080) {
                $text = 55;
                $adherence_level = 9;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 12960) {
                $query_date = $this->db->query("SELECT response,p_details_id FROM tbl_reports WHERE p_details_id='$hcw_id' AND  response='1' OR response='2' ");
                if ($query_date->num_rows() > 0) {
                    $text = 21;
                    $adherence_level = 10;
                    $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                }
            }
            if ($minutes == 14400) {
                $query_date = $this->db->query("SELECT response,p_details_id FROM tbl_reports WHERE p_details_id='$hcw_id AND response='2' ");
                if ($query_date->num_rows() > 0) {
                    $query_date_created = $this->db->query("SELECT date_received,msg FROM `tbl_logs_inbox` WHERE  msg LIKE '%no'  and mobile_no='$mob_no'");
                    if ($query_date_created->num_rows() > 0) {
                        $date = $query_date_created->result();
                        foreach ($date as $dates) {
                            $r_date = $dates->date_received;
                            $date_sent = strtotime($r_date);
                            $datetime = strtotime("now");
                            $substract = $datetime - $date_sent;
                            $r_days = $substract / 86400;
                            $days = round($r_days);



//check if response yes was received on day 4
                            if ($query_date_created->num_rows() < 0 && $days == 1) {
                                echo "</br>" . "Minutes after message was sent from tbl_outbox table = " . $r_mins . "</br> ";
                                $text = 23;
                                $adherence_level = 11;
                                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                            }
                        }
                    }
                }
            }
            if ($minutes == 17280) {

                $text = 26;
                $adherence_level = 12;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 20160) {
                $text = 55;
                $adherence_level = 13;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 23040) {

                $text = 27;
                $adherence_level = 14;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 24480) {
                $query_date = $this->db->query("SELECT response,p_details_id FROM tbl_reports WHERE p_details_id='$hcw_id' AND response='2' ");
                if ($query_date->num_rows() > 0) {

                    $query_date_created = $this->db->query("SELECT date_received,msg FROM `tbl_logs_inbox` WHERE  msg LIKE '%no'  and mobile_no='$mob_no'");
                    if ($query_date_created->num_rows() > 0) {
                        $date = $query_date_created->result();
                        foreach ($date as $dates) {
                            $r_date = $dates->date_received;
                            $date_sent = strtotime($r_date);
                            $datetime = strtotime("now");
                            $substract = $datetime - $date_sent;
                            $r_days = $substract / 86400;
                            $days = round($r_days);



                            //check if response yes was received on day 4
                            if ($query_date_created->num_rows() < 0 && $days == 1) {
                                echo "</br>" . "Minutes after message was sent from tbl_outbox table = " . $r_mins . "</br> ";
                                $text = 27;
                                $adherence_level = 15;
                                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                            }
                        }
                    }
                }
            }
            if ($minutes == 30240) {
                $text = 41;
                $adherence_level = 16;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 37440) {
                $text = 42;
                $adherence_level = 17;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 40320) {
                $text = 72;
                $adherence_level = 18;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 43200) {
                $text = 44;
                $adherence_level = 19;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 44640) {
                $text = 73;
                $adherence_level = 20;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 69120) {
                $text = 46;
                $adherence_level = 21;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 89280) {

                $text = 47;
                $adherence_level = 22;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 109440) {

                $text = 76;
                $adherence_level = 23;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 126720) {
                $text = 77;
                $adherence_level = 24;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 129600) {

                $text = 78;
                $adherence_level = 25;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 132480) {

                $text = 79;
                $adherence_level = 26;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 135360) {
                $text = 83;
                $adherence_level = 27;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 198720) {

                $text = 80;
                $adherence_level = 28;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 239040) {
                $text = 81;
                $adherence_level = 29;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 256320) {

                $text = 82;
                $adherence_level = 30;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 262080) {

                $text = 83;
                $adherence_level = 31;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }
            if ($minutes == 264960) {
                $text = 84;
                $adherence_level = 32;
                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
            }

            $query_response = $this->db->query("SELECT response from tbl_reports where p_details_id='$hcw_id' and response ='0'");
            if ($query_response->num_rows() > 0) {
                $query_date_created = $this->db->query("SELECT date_received,msg FROM `tbl_logs_inbox` WHERE msg LIKE '%no'  and mobile_no='$mob_no'");
                if ($query_date_created->num_rows() > 0) {
                    $date = $query_date_created->result();
                    foreach ($date as $dates) {
                        $r_date = $dates->date_received;
                        $date_sent = strtotime($r_date);
                        $datetime = strtotime("now");
                        $substract = $datetime - $date_sent;
                        $r_days = $substract / 86400;
                        $days = round($r_days);
                        $r_minutes = $substract * 60;
                        $minutes = round($r_minutes);

// echo "</br>" . "Minutes after message was sent from tbl_outbox table = " . $r_mins . "</br> ";
                            if ($minutes == 2880) {
                                $text = 13;
                                $adherence_level = 2;
                                $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                            }
                        
                    }
                }
            }

            $query_date_created = $this->db->query("SELECT date_received,msg FROM `tbl_logs_inbox` WHERE msg LIKE '%no'  and mobile_no='$mob_no'");
            if ($query_date_created->num_rows() > 0) {
                $date = $query_date_created->result();
                foreach ($date as $dates) {
                    $r_date = $dates->date_received;
                    $date_sent = strtotime($r_date);
                    $datetime = strtotime("now");
                    $substract = $datetime - $date_sent;
                    $r_days = $substract / 86400;
                    $days = round($r_days);
                    $r_minutes = $substract * 60;
                    $minutes = round($r_minutes);



                 
// echo "</br>" . "Minutes after message was sent from tbl_outbox table = " . $r_mins . "</br> ";
                        if ($minutes == 3600) {
                            $text = 15;
                            $adherence_level = 3;
                            $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                        }
                    
                }
            }

            $query_date_created = $this->db->query("SELECT date_received,msg FROM `tbl_logs_inbox` WHERE msg LIKE '%yes'  and mobile_no='$mob_no'");
            if ($query_date_created->num_rows() > 0) {
                $date = $query_date_created->result();
                foreach ($date as $dates) {
                    $r_date = $dates->date_received;
                    $date_sent = strtotime($r_date);
                    $datetime = strtotime("now");
                    $substract = $datetime - $date_sent;
                    $r_days = $substract / 86400;
                    $response_hour = $substract / 3600;
                    $res_hours = round($response_hour);
                    $days = round($r_days);

                    if ($minutes == 4320 && $res_hours <= 1) {
                        $text = 71;
                        $adherence_level = 4;
                        $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                    }
                }
            }
           
            $response = $this->check_responses($hcw_id);
            foreach ($response as $res) {
                $yes_no = $res->response;
                if ($yes_no == 0) {
                    if ($minutes == 6000) {
                        $text = 28;
                        $adherence_level = 6;
                        $this->messages_adherence($text, $adherence_level, $mob_no, $hcw_id);
                    }
                }
            }
        }
    }

    function age_groupp() {

        $get_dob = $this->db->query("Select tbl_patientdetails.id, tbl_patientdetails.DOB from tbl_patientdetails inner join tbl_master_facility "
                        . "on tbl_master_facility.code = tbl_patientdetails.facility_id ")->result_array();

        foreach ($get_dob as $value) {
            $id = $value['id'];
            $crnt_date = (int) date('Y');
            $dob = $value['DOB'];
            $mydate = (int) $dob;

            if (($crnt_date - $mydate) >= 15 && ($crnt_date - $mydate) <= 19) {
                $x = 1;
            }
            if (($crnt_date - $mydate) >= 20 && ($crnt_date - $mydate) <= 24) {
                $x = 2;
            }
            if (($crnt_date - $mydate) >= 25 && ($crnt_date - $mydate) <= 29) {
                $x = 3;
            }
            if (($crnt_date - $mydate) >= 30 && ($crnt_date - $mydate) <= 34) {
                $x = 4;
            }
            if (($crnt_date - $mydate) >= 35 && ($crnt_date - $mydate) <= 39) {
                $x = 5;
            }
            if (($crnt_date - $mydate) >= 40 && ($crnt_date - $mydate) <= 44) {
                $x = 6;
            }
            if (($crnt_date - $mydate) >= 45 && ($crnt_date - $mydate) <= 49) {
                $x = 7;
            }
            if (($crnt_date - $mydate) >= 50 && ($crnt_date - $mydate) <= 54) {
                $x = 8;
            }
            if (($crnt_date - $mydate) > 54) {
                $x = 9;
            }

            $this->db->query("UPDATE tbl_patientdetails set age_group = $x where id = $id");
        }
    }

    function getSubCountyPatients() {

        $query = "SELECT 
  tbl_master_facility.name AS facilityname,
  tbl_master_facility.code as mfl,
  tbl_county.name as county,
  tbl_sub_county.name as subcounty
FROM
  tbl_master_facility 
  
  INNER JOIN tbl_county 
    ON tbl_county.id = tbl_master_facility.county_id 
  INNER JOIN tbl_sub_county 
    ON tbl_sub_county.id = tbl_master_facility.Sub_County_ID ";
        return $this->db->query($query)->result_array();
    }

}
