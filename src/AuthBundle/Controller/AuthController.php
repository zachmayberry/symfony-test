<?php

namespace AuthBundle\Controller;


use AppBundle\Entity\Hospital;
use AppBundle\Entity\MedicalScience;
use AppBundle\Entity\User;
use AppBundle\Controller\BaseController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FOS\UserBundle\Model\UserInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use AuthBundle\Exception\InvalidPropertyUserException;
use AuthBundle\Exception\UserException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Class AuthController
 * @package ApiBundle\Controller
 */
class AuthController extends BaseController
{
    protected $userIdentityField = 'email';

    /**
     * Register a user
     */
    public function registerAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        // Get request payload
        $data = $request->request->all();

        // Check for existing user
        if (!empty($data['email']) && $userManager->findUserByUsernameOrEmail($data['email'])) {
            return new JsonResponse('A user with this email address already exists.', 409);
        }

        // Get doctor or normal user form
        $registerAsDoctor = (int)$request->get('type') === User::USER_TYPE_DOCTOR;

        // Check if all required data is provided
        if (empty($data['email']) || empty($data['password']) || empty($data['firstname']) || empty($data['lastname']) ||
            empty($data['gender'])) {
            return new JsonResponse('Please fill out all required fields.', 409);
        }

        // If register as doctor, also check if additional required data is provided
        if ($registerAsDoctor && (empty($data['hospital']) || empty($data['phone']) || empty($data['medicalScience']))) {
            return new JsonResponse('Please fill out all required fields. 2', 409);
        }

        // Check for valid email
        $emailConstraint = new EmailConstraint();
        //$emailConstraint->message = 'Your customized error message';

        $errors = $this->get('validator')->validate(
            $data['email'],
            $emailConstraint
        );

        // If register as doctor, also check if additional required data is provided
        if (count($errors)) {
            return new JsonResponse("Please enter a valid email address.", 409);
        }

        // Check if all required data is provided
        if (empty($data['acceptedTerms']) || !$data['acceptedTerms']) {
            return new JsonResponse('You have to accept the terms.', 409);
        }

        // Check if all required data is provided
        if (empty($data['dob'])) {
            return new JsonResponse('Please enter your date of birth', 409);
        }

        // Create user and set data
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setEmail($data['email']);
        $user->setUsername($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setPlainPassword($data['password']);
        $user->setDob(new \DateTime($data['dob']));

        // set additional data if registering as doctor
        if ($registerAsDoctor) {

            $user->setApproved(false);
            $user->setType(User::USER_TYPE_DOCTOR);
            $user->addRole('ROLE_DOCTOR'); // TODO: set this on approve

            $user->setPhone($data['phone']);
            //$user->setCertificateFile($data['certificateFileName']);

            $em = $this->getDoctrine()->getManager();

//            // add hospital if not available
//            $hospitalRepository = $em->getRepository('AppBundle:Hospital');
//            if (null === $hospital = $hospitalRepository->findOneByTitle($data['hospital'])) {
//                $hospital = new Hospital();
//                $hospital->setTitle($data['hospital']);
//                $em->persist($hospital);
//            }
//            $user->setHospital($hospital);
            $user->setHospital($data['hospital']);

//            // add medical science if not available
//            $medicalScienceRepository = $em->getRepository('AppBundle:MedicalScience');
//            if (null === $medicalScience = $medicalScienceRepository->findOneByTitle($data['medicalScience'])) {
//                $medicalScience = new MedicalScience();
//                $medicalScience->setTitle($data['medicalScience']);
//                $em->persist($medicalScience);
//            }
//            $user->setMedicalScience($medicalScience);
            $user->setMedicalScience($data['medicalScience']);
        }

        // disable user and set confirmation token
        $user->setEnabled(false);

        if ($registerAsDoctor) {
            // send email to notify about approval process
            $this->sendUserMail($user, "confirmation_doctor");

            // inform admin about new doctor registration
            if ($this->getParameter('notify_admin_on_doctor_signup')) {
                $this->sendAdminNotificationMail($user);
            }
        }
        else {
            // send confirmation mail
            $this->sendConfirmationMail($user);
        }

        // save user
        $userManager->updateUser($user);

        // send JSON response
        $arrResponse = [
            'isRegisterAsDoctor' => $registerAsDoctor,
            'email' => $user->getEmail(),
        ];

        return new JsonResponse($arrResponse, 201);
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function confirmAjaxAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $token = $request->get('token');
        $email = $request->get('email');

        if (!$email) {
            return new JsonResponse('Something went wrong. Please activate your account by clicking the link in the confirmation email.', 409);
        }
        if (!$token) {
            return new JsonResponse('You must enter your token.', 409);
        }

        $user = $userManager->findUserBy([
            'confirmationToken' => $token,
            'email' => $email,
        ]);

        if (null === $user) {
            return new JsonResponse(sprintf('Your code "%s" is not valid.', $token), 409);
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        #$this->sendUserMail($user, 'confirmed');

        $userManager->updateUser($user);

        return $this->renderToken($user);
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param Request $request
     * @param string  $email
     * @param string  $token
     *
     * @return RedirectResponse
     */
    public function confirmAction(Request $request, $email, $token)
    {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserBy([
            'confirmationToken' => $token,
            'email' => $email,
        ]);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('Your code "%s" is not valid', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $userManager->updateUser($user);

        $token = $this->generateToken($user);
        $refreshToken = $this->generateRefreshToken($user);
        $url = $this->generateUrl('homepage') . "?token=$token&refresh_token=$refreshToken";
        $response = new RedirectResponse($url);

        return $response;
    }

//    /**
//     * Tell the user his account is now confirmed.
//     */
//    public function confirmedAction()
//    {
//        $user = $this->getUser();
//        if (!is_object($user) || !$user instanceof UserInterface) {
//            throw new AccessDeniedException('This user does not have access to this section.');
//        }
//
//        $response = $this->render('@FOSUser/Registration/confirmed.html.twig', array(
//            'user' => $user,
//            'targetUrl' => $this->getTargetUrlFromSession(),
//        ));
//
//        // generate token and store in cookie to be logged in automatically after redirect to terminal page
//        $token = $this->generateToken($user);
//        $cookie = new Cookie('token', $token, strtotime('now + 60 minutes'));
//        $response->headers->setCookie($cookie);
//
//        return $response;
//    }

    /**
     * Processes user authentication from email/password.
     *
     * @return JsonResponse The authentication token
     */
    public function loginAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * Registers and authenticates User from a facebook OAuth Response.
     *
     * @param string $provider
     * @param Request $request
     *
     * @return object The authentication token
     */
    public function loginFromOAuthResponseAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $provider = $request->get('provider');

        switch ($provider) {

            case "facebook":

                $name = $this->splitName($request->get('name'));

                // get user data
                $data = [
                    $this->userIdentityField => $request->get('email'),
                    'facebook_id' => $request->get('id'),
                    'facebook_access_token' => $request->get('accessToken'),
                    'first_name' => $name['first_name'],
                    'last_name' => trim($name['middle_name'] . ' ' . $name['last_name']),
                ];

                // get users facebook image
                $pictureData = $request->get('picture');
                $uploadableFile = null;
                if (is_array($pictureData)) {
                    if (!$pictureData['data']['is_silhouette']) {
                        $image = $this->grabFacebookUserImage($data['facebook_id']);
                        $uploadableFile = new UploadedFile($image, $image, null, null, null, true);
                    }
                }

                if (true !== $this->isValidFacebookAccount($data['facebook_id'], $data['facebook_access_token'])) {
                    throw new InvalidPropertyUserException('The given facebook_id does not correspond to a valid acount');
                }

                // find user by facebook id
                $user = $userManager->findUserBy(['facebookId' => $data['facebook_id']]);
                if ($user) {
                    if ($uploadableFile) {
                        $user->setProfileImageFile($uploadableFile);
                        $userManager->updateUser($user);
                    }
                    return $this->renderToken($user);
                }

                // find user by username field (email)
                $user = $userManager->findUserBy([$this->userIdentityField => $data[$this->userIdentityField]]);
                if ($user) {
                    if ($uploadableFile) {
                        $user->setProfileImageFile($uploadableFile);
                    }
                    $user->setFacebookId($data['facebook_id']);
                    $userManager->updateUser($user);
                    return $this->renderToken($user);
                }

                // otherwise create new user
                $data['password'] = $this->generateRandomPassword();
                $user = $this->createUser($data, $userManager, 'facebook');
                if ($uploadableFile) {
                    $user->setProfileImageFile($uploadableFile);
                    $userManager->updateUser($user);
                }
                return $this->renderToken($user);

                break;
            case "google":

                $accessToken = $request->get('accessToken');
                #$url = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=" . $accessToken;
                #$result = file_get_contents($url);
                $profileObj = $request->get('profileObj');

                // get user data
                $data = [
                    $this->userIdentityField => $profileObj['email'],
                    'google_id' => $profileObj['googleId'],
                    'google_access_token' => $accessToken,
                    'first_name' => $profileObj['givenName'],
                    'last_name' => $profileObj['familyName'],
                ];

                // get users profile image
                $uploadableFile = null;
                /*if ($image = $this->grabGoogleProfileImage($profileObj['googleId'])) {
                    $uploadableFile = new UploadedFile($image, $image, null, null, null, true);
                    var_dump($pictureData);die;
                }*/

                if (true !== $this->isValidGoogleAccount($data['google_id'], $data['google_access_token'])) {
                    throw new InvalidPropertyUserException('The given google_id does not correspond to a valid acount');
                }

                // find user by google id
                $user = $userManager->findUserBy(['googleId' => $data['google_id']]);
                if ($user) {
                    if ($uploadableFile) {
                        $user->setProfileImageFile($uploadableFile);
                        $userManager->updateUser($user);
                    }
                    return $this->renderToken($user);
                }

                // find user by username field (email)
                $user = $userManager->findUserBy([$this->userIdentityField => $data[$this->userIdentityField]]);
                if ($user) {
                    if ($uploadableFile) {
                        $user->setProfileImageFile($uploadableFile);
                    }
                    $user->setGoogleId($data['google_id']);
                    $userManager->updateUser($user);
                    return $this->renderToken($user);
                }

                // otherwise create new user
                $data['password'] = $this->generateRandomPassword();
                $user = $this->createUser($data, $userManager, 'google');
                if ($uploadableFile) {
                    $user->setProfileImageFile($uploadableFile);
                    $userManager->updateUser($user);
                }
                return $this->renderToken($user);

                break;

            default:
                throw new Exception("Can not login. No provider given.");
        }
    }

    /**
     * Creates a new User.
     *
     * @param array $data
     * @param bool  $isOAuth
     *
     * @return UserInterface $user
     */
    protected function createUser(array $data, $userManager, $provider)
    {
        //$userManager = $this->get('fos_user.user_manager');

        $user = $userManager->createUser()
            ->setUsername($data[$this->userIdentityField])
            ->setEmail($data[$this->userIdentityField])
            ->setEnabled(true)
            ->setPlainPassword($data['password'])
        ;

        // Set provider id
        $attribute = $provider . '_id';
        $setter = 'set' . ucfirst($provider) . 'Id';
        if (isset($data[$attribute])) {
            $user->$setter($data[$attribute]);
        }

        // Set provider access token
        $attribute = $provider . '_access_token';
        $setter = 'set' . ucfirst($provider) . 'AccessToken';
        if (isset($data[$attribute])) {
            $user->$setter($data[$attribute]);
        }

        if ($data['first_name']) {
            $user->setFirstname($data['first_name']);
        }
        if ($data['first_name']) {
            $user->setLastname($data['last_name']);
        }

        /*        if ($data['profile_image']) {
                    $uploadableFile = new UploadedFile($data['profile_image'], $data['profile_image'], null, null, null, true);
                    $user->setProfileImageFile($uploadableFile);
                }
        */
        // set date of birth to prevent errors
        $user->setDob(new \DateTime());

        try {
            $userManager->updateUser($user);
        } catch (\Exception $e) {
            $message = $e->getMessage() ?: 'An error occured while creating the user.';
            throw new UserException(422, $message, $e);
        }
        return $user;
    }

    /**
     * Generates a JWT from given User.
     *
     * @param UserInterface $user
     * @param int           $statusCode
     *
     * @return JsonResponse
     */
    protected function renderToken(UserInterface $user, $statusCode = 200)
    {
        $body = [
            'token'         => $this->generateToken($user),
            'refresh_token' => $this->generateRefreshToken($user),
            'user'          => $user->getUsername(),
        ];
        return new JsonResponse($body, $statusCode);
    }

    /**
     * Create JWT (Json Web Token)
     *
     * @param $username
     * @return string
     */
    function generateToken(UserInterface $user)
    {
        // Default token data with user roles
        $token = $this->get('lexik_jwt_authentication.jwt_manager')->create($user);

        // Custom token data
        /*$token = $this->get('lexik_jwt_authentication.encoder')
            ->encode([
                'username' => $user->getUsername(),
                'exp' => time() + 3600 // 1 hour expiration
            ]);*/

        return $token;
    }

    /**
     * Provides a refresh token.
     *
     * @param UserInterface $user
     * @return string The refresh Json Web Token.
     */
    protected function generateRefreshToken(UserInterface $user)
    {
        #var_dump("UPDATE");die;
        $refreshTokenManager = $this->get('gesdinet.jwtrefreshtoken.refresh_token_manager');
        $refreshToken = $refreshTokenManager->getLastFromUsername($user->getUsername());
        $refreshTokenTtl = $this->getParameter('jwt_token_ttl');

        $isExistingRefreshToken = $refreshToken instanceof RefreshToken;

        if (!$isExistingRefreshToken || !$refreshToken->isValid()) {

            // create if not existing
//            if (!$isExistingRefreshToken) {
                $refreshToken = $refreshTokenManager->create();
//            }

            // update expiration date
            $expirationDate = new \DateTime();
            $expirationDate->modify(sprintf('+%s seconds', $refreshTokenTtl));

            $refreshToken->setUsername($user->getUsername());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid($expirationDate);

            $refreshTokenManager->save($refreshToken);
        }

        return $refreshToken->getRefreshToken();
    }

    /**
     * @param int    $facebookId          Facebook account id
     * @param string $facebookAccessToken Facebook access token
     *
     * @return bool Facebook account status
     */
    protected function isValidFacebookAccount($id, $accessToken)
    {
        $client = new \Goutte\Client();
        $client->request('GET', sprintf('https://graph.facebook.com/me?access_token=%s', $accessToken));
        $response = json_decode($client->getResponse()->getContent());
        if (isset($response->error)) {
            throw new InvalidPropertyUserException($response->error->message);
        }
        return $response->id == $id;
    }

    /**
     * @param int    $googleId          google account id
     * @param string $googleAccessToken google access token
     *
     * @return bool google account status
     */
    protected function isValidGoogleAccount($id, $accessToken)
    {
        $client = new \Goutte\Client();
        $client->request('GET', sprintf('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=%s', $accessToken));
        $response = json_decode($client->getResponse()->getContent());
        if (isset($response->error)) {
            throw new InvalidPropertyUserException($response->error->message);
        }
        return $response->id == $id;
    }

    /**
     * Generates a random password of 8 characters.
     *
     * @return string
     */
    protected function generateRandomPassword()
    {
        $tokenGenerator = $this->get('fos_user.util.token_generator');
        return substr($tokenGenerator->generateToken(), 0, 8);
    }

    /**
     * Grab profile image from Facebook account
     *
     * @param string $url
     * @param string $fileExt
     *
     * @return string
     */
    protected function grabFacebookUserImage($id)
    {
        $saveTo = $this->getParameter('kernel.root_dir') . '/../' . $this->getParameter('tmp_path') . '/' . $id . '.jpg';

        $url = "https://graph.facebook.com/$id/picture?width=300&height=300";
        $data = file_get_contents($url);
        $fp = fopen($saveTo,"wb");

        if (!$fp) return null;

        fwrite($fp, $data);
        fclose($fp);

        return $saveTo;
    }


    /**
     * Grab profile image from Google+ account
     *
     * @param string $url
     * @param string $fileExt
     *
     * @return string
     */
    protected function grabGoogleProfileImage($id)
    {
        $apiKey = $this->getParameter('google_client_id');
        $saveTo = $this->getParameter('kernel.root_dir') . '/../' . $this->getParameter('tmp_path') . '/' . $id . '.jpg';
        $url = "https://www.googleapis.com/plus/v1/people/$id?fields=image&key=$apiKey";

        $data = file_get_contents($url);
        $dataObj = json_decode($data);

        return null;
        var_dump($dataObj);die;

        $fp = fopen($saveTo,"wb");

        if (!$fp) return null;

        fwrite($fp, $data);
        fclose($fp);

        return $saveTo;
    }

    /**
     * Get firstname, middlename and lastname from name string
     *
     * @link https://stackoverflow.com/questions/13637145/split-text-string-into-first-and-last-name-in-php
     *
     * @param $name
     * @return array|bool|string
     */
    function splitName($name) {
        $parts = array();

        while ( strlen( trim($name)) > 0 ) {
            $name = trim($name);
            $string = preg_replace('#.*\s([\w-]*)$#', '$1', $name);
            $parts[] = $string;
            $name = trim( preg_replace('#'.$string.'#', '', $name ) );
        }

        if (empty($parts)) {
            return false;
        }

        $parts = array_reverse($parts);
        $name = array();
        $name['first_name'] = $parts[0];
        $name['middle_name'] = (isset($parts[2])) ? $parts[1] : '';
        $name['last_name'] = (isset($parts[2])) ? $parts[2] : ( isset($parts[1]) ? $parts[1] : '');

        return $name;
    }

    /**
     * Get rid on any fields that don't appear in the form
     *
     * @param Request $request
     * @param Form $form
     */
    protected function removeExtraFields(Request $request, Form $form)
    {
        $data     = $request->request->all();
        $children = $form->all();
        $data     = array_intersect_key($data, $children);
        $request->request->replace($data);
    }


    protected function sendConfirmationMail(UserInterface $user)
    {
        // generate token if not has one
        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($this->generateConfirmationToken());
        }

        // generate confirmation URL
        $urlParams = [
            'email' => $user->getEmail(),
            'token' => $user->getConfirmationToken(),
        ];
        $confirmationUrl = $this->generateUrl('auth_registration_confirm', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $parameters = [
            'confirmationUrl' => $confirmationUrl,
            'token' => $user->getConfirmationToken(),
        ];

        $this->sendUserMail($user, "confirmation", $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function generateConfirmationToken()
    {
        $newToken = strtoupper($this->generateRandomString(5));

        $userManager = $this->get('fos_user.user_manager');

        // create new token if a user with this token already exists
        if ($userManager->findUserByConfirmationToken($newToken) !== null) {
            return $this->generateConfirmationToken();
        }

        return $newToken;
    }

    public function generateRandomString($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }



    /**
     * Request reset user password: submit form and send email.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function requestPasswordResetAction(Request $request)
    {
        $email = $request->request->get('email');

        if (!$email) {
            return new JsonResponse("Please enter an email address", 409);
        }

        $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($email);

        if (!$user) {
            return new JsonResponse("A user with this email address doesn't exist", 404);
        }

        if (!$user->isEnabled()) {
            return new JsonResponse("The user with this email address is not confirmed yet", 404);
        }

        $this->sendResettingMail($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->get('fos_user.user_manager')->updateUser($user);

        return new JsonResponse("Please check your emails. We have sent you a link to reset your password.", 200);

    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function confirmResetPasswordAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $token = $request->get('token');
        $email = $request->get('email');
        $isXmlHttpRequest = !$request->get('noajax'); // !$request->isXmlHttpRequest() (not working)

        if (!$email) {
            return new JsonResponse('Something went wrong. Please reset you password by clicking the link in the email.', 409);
        }
        if (!$token) {
            return new JsonResponse('You must enter your code.', 409);
        }

        $user = $userManager->findUserBy([
            'confirmationToken' => $token,
            'email' => $email,
        ]);

        if (null === $user) {
            return new JsonResponse(sprintf('Your code "%s" is not valid.', $token), 409);
        }
        else if (!$user->isEnabled()) {
            return new JsonResponse("The user with this email address is not confirmed yet", 404);
        }

        $user->setConfirmationToken(null);
        $userManager->updateUser($user);

        // redirect for non ajax requests
        if (!$isXmlHttpRequest) {

            $token = $this->generateToken($user);
            $refreshToken = $this->generateRefreshToken($user);
            $url = $this->generateUrl('homepage') . "?change_password=_&token=$token&refresh_token=$refreshToken";
            $response = new RedirectResponse($url);

            return $response;
        }

        return $this->renderToken($user);
    }


    /**
     * Change user password.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            return new JsonResponse('This user does not have access to this section.', 409);
        }

        $password = $request->get('plainPassword');
        $passwordCopy = $request->get('plainPasswordCopy');

        if (!$password) {
            return new JsonResponse('You have entered no password.', 409);
        }

        if (strlen($password) < 8) {
            return new JsonResponse('The password must have at least 8 characters.', 409);
        }

        if (strlen($password) >= 255) {
            return new JsonResponse('The password is too long.', 409);
        }

        if ($password !== $passwordCopy) {
            return new JsonResponse('The passwords do not match.', 409);
        }

        $userManager = $this->get('fos_user.user_manager');
        $user->setPlainPassword($password);
        $userManager->updateUser($user);

        return new JsonResponse('Password has been changed.', 200);
    }


    protected function sendResettingMail(UserInterface $user)
    {
        // set confirmation token
        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($this->generateConfirmationToken());
        }

        // generate confirmation URL
        $urlParams = [
            'email' => $user->getEmail(),
            'token' => $user->getConfirmationToken(),
            'noajax' => true,
        ];
        $confirmationUrl = $this->generateUrl('auth_resetting_confirm', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $parameters = [
            'confirmationUrl' => $confirmationUrl,
            'token' => $user->getConfirmationToken(),
        ];

        $this->sendUserMail($user, "password_resetting", $parameters);
    }

    private function sendUserMail(User $user, $templateName, $parameters = [])
    {
        try {
            $template = $this->get('twig')->loadTemplate(":email:$templateName.email.twig");

            $parameters['user'] = $user;

            $subject = $template->renderBlock('subject', $parameters);
            $bodyText = $template->renderBlock('body_text', $parameters);
            $bodyHtml = $template->renderBlock('body_html', $parameters);

            $message = new \Swift_Message();
            $message->setSubject($subject);
            $message->setBody($bodyText, 'text/plain');
            $message->addPart($bodyHtml, 'text/html');

            $message->setFrom($this->getParameter('default_from_email'), $this->getParameter('default_from_name'));
            $message->setTo($user->getEmail());

            $this->get('mailer')->send($message);
        }
        catch (Exception $e) {
            // ... sorry user, no info
            // TODO: log this somehow
            throw $e;
        }
    }

    private function sendAdminNotificationMail(User $user)
    {
        if ($adminEmail = $this->getParameter('notify_admin_email')) {

            try {
                $template = $this->get('twig')->loadTemplate(":email:admin_notification_on_doctor_signup.email.twig");

                $parameters['user'] = $user;

                $subject = $template->renderBlock('subject', $parameters);
                $bodyText = $template->renderBlock('body_text', $parameters);
                $bodyHtml = $template->renderBlock('body_html', $parameters);

                $message = new \Swift_Message();
                $message->setSubject($subject);
                $message->setBody($bodyText, 'text/plain');
                $message->addPart($bodyHtml, 'text/html');

                $message->setFrom($this->getParameter('default_from_email'), $this->getParameter('default_from_name'));
                $message->setTo($adminEmail);

                $this->get('mailer')->send($message);
            }
            catch (Exception $e) {
                // ... sorry user, no info
                // TODO: log this somehow
                throw $e;
            }
        }
    }
}