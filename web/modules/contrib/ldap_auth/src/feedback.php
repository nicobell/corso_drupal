<?php
namespace Drupal\ldap_auth;
use Drupal\ldap_auth\Utilities;

class   feedback {
    public static function ldap_auth_feedback() {
        global $base_url;
        $feedback_url = $base_url.'/feedback';
        $_SESSION['mo_other'] = 'True';
        $form_id = $_POST['form_id'];
        $form_token = $_POST['form_token'];
        $admin_email=Utilities::getCustomerEmail();
?>
<html>
<head>
    <title>Feedback</title>
    <link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <style>
        .ldap_loader {
            margin: auto;
            display: block;
            border: 5px solid #f3f3f3; /* Light grey */
            border-top: 5px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#myModal").modal({
                backdrop: 'static',
                keyboard: false
            });
        });


        $(function() {
            $(".button").click(function () {
                document.getElementById('ldap_loader').style.display = 'block';
                var reason = $("input[name='deactivate_plugin']:checked").val();
                var q_feedback = document.getElementById("query_feedback").value;
                return false;
            });
        })
    </script>
</head>
<body>
    <div class="container">
        <div class="modal fade" id="myModal" role="dialog" style="background: rgba(0,0,0,0.1);">
            <div class="modal-dialog" style="width: 500px;">
                <div class="modal-content" style="border-radius: 20px">
                    <div class="modal-header" style="padding: 25px; border-top-left-radius: 20px; border-top-right-radius: 20px; background-color: #8fc1e3;">
                        <h4 class="modal-title" style="color: white; text-align: center;">Hey, it seems like you want to deactivate Active Directory Integration / LDAP Integration - NTLM & Kerberos Login</h4>
                        <hr>
						<h4 style="text-align: center; color: white;">What happened?</h4>
                    </div>
                    <div class="modal-body" style="font-size: 11px; padding-left: 25px; padding-right: 25px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; background-color: #ececec;">
                        <form name="f" action="<?php echo $feedback_url; ?>" id="mo_feedback">
                            <div>
                                <p>
                                    <?php
                                    if(empty(\Drupal::config('ldap_auth.settings')->get('miniorange_ldap_customer_admin_email'))) { ?>
                                    <br>Email ID: <input onblur="validateEmail(this)" class="form-control"
                                        type="email" id="miniorange_feedback_email" required value= <?php echo $admin_email; ?>
                                        name="miniorange_feedback_email"/>
                                    <p style="display: none;color:red" id="email_error">Invalid Email</p>
                                    <?php
                                    } ?>
                                    <br>
                                    <?php
                                    $deactivate_reasons = array(
                                        t("Not Working"),
                                        t("Not receiving OTP during registration"),
                                        t("Does not have the features I'm looking for"),
                                        t("Redirecting back to login page after Authentication"),
                                        t("Confusing interface"),
                                        t("Bugs in the plugin"),
                                        t("Other reasons: "),
                                    );
                                    foreach ($deactivate_reasons as $deactivate_reasons) {
                                        ?>
                                    <div  class="radio" style="padding:2px;font-size: 8px">
                                            <label style="font-weight:normal;font-size:14.6px;color:maroon" for="<?php echo $deactivate_reasons; ?>">
                                                <input type="radio" name="deactivate_plugin" value="<?php echo $deactivate_reasons;?>" required>
                                                <?php echo $deactivate_reasons; ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                <input type="hidden" name="form_token" value=<?php echo $form_token ?> >
                                <input type="hidden" name="form_id" value= <?php echo $form_id ?>>
                                <br>
                                <textarea class="form-control" id="query_feedback" name="query_feedback" rows="4" cols="50" placeholder="Write your query here"></textarea>
                                <br><br>
                                <div class="mo2f_modal-footer" style="margin-bottom: 5% !important;">
                                    <input type="submit" id="submit_button" name="miniorange_feedback_submit" class="button btn btn-primary" value="Submit and Continue" style=" display: block; font-size: 11px;float: left;margin-bottom: 15%;"/>
                                    <input type="submit" formnovalidate="formnovalidate" style="margin: auto; display: block; font-size: 11px; float: right;" name="miniorange_feedback_skip" class="btn btn-link" value="Skip" />
                                </div>
                                <?php
                                    foreach($_POST as $key => $value) {
                                        self::hiddenLDAPFields($key,$value);
                                    }
                                ?>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
        <?php
        exit;
    }


    public static function hiddenLDAPFields($key,$value)
    {
        $hiddenLDAPField = "";
        $value2 = array();
        if(is_array($value)) {
            foreach($value as $key2 => $value2)
            {
                if(is_array($value2)){
                  $hiddenLDAPField($key."[".$key2."]",$value2);
                } else {
                  $hiddenLDAPField = "<input type='hidden' name='".$key."[".$key2."]"."' value='".$value2."'>";
                }
            }
        }else{
          $hiddenLDAPField = "<input type='hidden' name='".$key."' value='".$value."'>";
        }
        echo $hiddenLDAPField;
    }
}
