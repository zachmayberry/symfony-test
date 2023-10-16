<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use phpseclib\Net\SFTP;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Yaml\Yaml;

class DefaultController extends Controller
{
    /**
     * Render the single page app with custom settings
     *
     * @param Request $request
     * @return Response
     */
    private function renderSPA(Request $request, $customParams)
    {
        $twigParams = array_merge([
            'fb_app_id' => $this->getParameter('facebook_client_id'),
            'title' => $this->getParameter('page_title_web'),
            'og_url' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
            'og_type' => 'website',
            'og_title' => $this->getParameter('page_title_web'),
            'og_siteName' => $this->getParameter('page_name'),
            'og_description' => $this->getParameter('page_description'),
            'og_image' => $request->getSchemeAndHttpHost() . '/images/logo_260x260.png',
            'og_image_width' => '260',
            'og_image_height' => '260',
        ], $customParams);

        // get hashes from webpack deploy
        if ($this->container->get('kernel')->getEnvironment() !== 'dev') {

            // Get hashes from webpack hashes.yml file
            $hashesYmlFile = $this->getParameter('kernel.root_dir') . '/../hashes.yml';
            $hashes = Yaml::parse(file_get_contents($hashesYmlFile));

            $twigParams = array_merge_recursive($twigParams, $hashes);
        }

        return $this->render('default/index.html.twig', $twigParams);
    }

    /**
     * Get the full page title for a given web sub page
     *
     * @param $subPageTitle
     * @return string
     */
    private function getWebPageTitle($subPageTitle)
    {
        return $subPageTitle . $this->getParameter('page_title_delimeter') . $this->getParameter('page_title_web');
    }

    /**
     * Get the full page title for a given app sub page
     *
     * @param $subPageTitle
     * @return string
     */
    private function getAppPageTitle($subPageTitle)
    {
        return $subPageTitle . $this->getParameter('page_title_delimeter') . $this->getParameter('page_title_app');
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $twigParams = [];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/news", name="news")
     * @param Request $request
     * @return Response
     */
    public function newsAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('News'),
            'og_title' => 'News',
            'og_image' => $this->get('liip_imagine.cache.manager')->getBrowserPath('news-image.png', 'quad_400'),
            'og_image_width' => '400',
            'og_image_height' => '400',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/news/view/{id}", name="news_detail")
     * @param Request $request
     * @return Response
     */
    public function newsDetailAction(Request $request, $id)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('News'),
            'og_title' => 'News',
            'og_image' => $this->get('liip_imagine.cache.manager')->getBrowserPath('news-image.png', 'quad_400'),
            'og_image_width' => '400',
            'og_image_height' => '400',
        ];

        // get news and overwrite meta tags
        if ($news = $this->getDoctrine()->getRepository('AppBundle:News')->find($id)) {
            $twigParams['title'] = $this->getWebPageTitle($news->getTitle());
            $twigParams['og_type'] = 'article';
            $twigParams['og_title'] = $news->getTitle();
            $twigParams['og_description'] = $news->getTeaser();

            // override image only if defined
            if ($news->getImageName()) {

                // get original image path
                $image = $this->getParameter('kernel.root_dir') . '/../uploads/news/' . $news->getImageName();

                if (is_file($image)) {

                    // get size of news image
                    list($width, $height) = getimagesize($image);

                    $twigParams['og_image'] = $this->get('liip_imagine.cache.manager')->getBrowserPath($news->getImageName(), 'original_image');
                    $twigParams['og_image_width'] = $width;
                    $twigParams['og_image_height'] = $height;
                }

            }
        }

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/references", name="references")
     * @param Request $request
     * @return Response
     */
    public function referencesAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('References'),
            'og_title' => 'References',
            'og_image' => $this->get('liip_imagine.cache.manager')->getBrowserPath('reference-image.png', 'quad_400'),
            'og_image_width' => '400',
            'og_image_height' => '400',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/faq", name="faq")
     * @param Request $request
     * @return Response
     */
    public function faqAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('FAQ'),
            'og_title' => 'FAQ',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/donate", name="donate")
     * @param Request $request
     * @return Response
     */
    public function donateAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('Donate & Help'),
            'og_title' => 'Donate & Help',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $twigParams = [
            'og_title' => $this->getWebPageTitle('Login'),
            'og_title' => 'Login',
            'og_image' => $this->get('liip_imagine.cache.manager')->getBrowserPath('user-profile-image.png', 'quad_400'),
            'og_image_width' => '400',
            'og_image_height' => '400',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * @Route("/signup", name="signup")
     * @param Request $request
     * @return Response
     */
    public function signupAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getWebPageTitle('Signup'),
            'og_title' => 'Signup',
            'og_image' => $this->get('liip_imagine.cache.manager')->getBrowserPath('user-profile-image.png', 'quad_400'),
            'og_image_width' => '400',
            'og_image_height' => '400',
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * Global endpoint for all app pages (therefore we need to allow "/" in parameters)
     *
     * @Route("/app/{paramters}", name="app", requirements={"paramters"=".+"})
     * @param Request $request
     * @return Response
     */
    public function appAction(Request $request)
    {
        $twigParams = [
            'title' => $this->getParameter('page_title_app'),
            'og_title' => $this->getParameter('page_title_app'),
        ];

        return $this->renderSPA($request, $twigParams);
    }

    /**
     * Endpoint to verify username and password for HtAccessFake react component
     *
     * @Route("/htafauth", name="htaf")
     * @Method({"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function htAccessFakeAction(Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');

        $hash = md5($username . $password);

        return new JsonResponse([
            'payload' => $hash,
        ]);
    }

}
