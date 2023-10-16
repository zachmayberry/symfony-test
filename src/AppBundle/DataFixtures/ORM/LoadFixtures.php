<?php


namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Nelmio\Alice\Fixtures;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LoadFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $objects = Fixtures::load(
            __DIR__ . '/fixtures.yml',
            $manager,
            [
                'providers' => [$this]
            ]
        );
    }

    public function phone()
    {
        $pre = [
            '0171',
            '0176',
            '0172',
            '0152',
            '0160',
            '0177',
            '089',
            '08161',
        ];

        return $pre[array_rand($pre)] . mt_rand(1000000, 99999999);
    }

    public function therapy()
    {
        $array = [
            'Parkinson\'s Marches',
            'Dialysis Classic',
            'Listen with Chemo Tone',
            'Chemotherapy',
            'NICU Respiratory',
            'Dyslexia',
            'Sleep - Goldberg',
            'Memory Increase',
            'CBD - PTSD Fear Memory Decrease',
            'Palliative Tones',
            'Intensive Care Classic',
        ];

        return $array[array_rand($array)];
    }

    public function therapyQuality()
    {
        $array = [
            'Low quality speakers',
            'High quality speakers',
            'Quality Headphones',
            'All speakers',
        ];

        return $array[array_rand($array)];
    }

    public function externalLinks()
    {
        $array = [
            'http://www.example.com',
            'http://www.healthtunes.org',
        ];

        return $array[array_rand($array)];
    }

    public function duration($type, $i = -1)
    {
        switch ($type) {
            case 'marches':
                $array = [
                    196,
                    172,
                    176,
                    572,
                    420,
                    214,
                    55,
                    215,
                    192,
                    172,
                ];
                break;
            case 'music':
                $array = [
                    182,
                    186,
                    203,
                    248,
                    94,
                    1652,
                    160,
                    231,
                    489,
                    269,
                ];
                break;
            default:
                $array = [];
                throw new \Exception("No valid namespace in duration() fixture loader.");
        };

        if ($i === -1) {
            $i = array_rand($array);
        }

        return $array[$i];
    }

    public function uploadFile($type, $i = -1)
    {
        switch ($type) {
            case 'albumCover':
                $array = [
                    'HT_TONES.jpg',
                    'HT_CLASSICS.jpg',
                    'HT_ENVIRONMENTS.jpg',
                    'HT_CHARTS.jpg',
                ];
                break;
            case 'artistPic':
                $array = [
                    'adele2.png',
                    'bob.png',
                    'carly.png',
                    'ellie.png',
                    'fun.png',
                    'gotye.png',
                    'imagineDragons.png',
                    'katy.png',
                    'kesha.png',
                    'ladyAntebellum.png',
                    'lmfao.png',
                    'ludwig.jpg',
                    'macklemoreLewis.png',
                    'maroon.png',
                    'pitbull.png',
                    'robin.png',
                    'strauss.png',
                    'strauss2.png',
                    'train.png',
                    'usher.png',
                    'walter.jpg',
                    'adele2.png',
                ];
                break;
            case 'HEAL026':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                ];
                break;
            case 'HEAL029':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                    '09.mp3',
                    '10.mp3',
                    '11.mp3',
                    '12.mp3',
                    '13.mp3',
                    '14.mp3',
                ];
                break;
            case 'HEAL031':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                    '09.mp3',
                    '10.mp3',
                    '11.mp3',
                    '12.mp3',
                    '13.mp3',
                    '14.mp3',
                ];
                break;
            case 'HEAL108':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                ];
                break;
            case 'HEAL111':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                    '09.mp3',
                    '10.mp3',
                    '11.mp3',
                    '12.mp3',
                    '13.mp3',
                    '14.mp3',
                ];
                break;
            case 'HEAL210':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                    '09.mp3',
                    '10.mp3',
                    '11.mp3',
                    '12.mp3',
                    '13.mp3',
                    '14.mp3',
                    '15.mp3',
                    '16.mp3',
                    '17.mp3',
                    '18.mp3',
                    '19.mp3',
                    '20.mp3',
                ];
                break;
            case 'HEAL500':
                $array = [
                    '01.mp3',
                    '02.mp3',
                    '03.mp3',
                    '04.mp3',
                    '05.mp3',
                    '06.mp3',
                    '07.mp3',
                    '08.mp3',
                    '09.mp3',
                    '10.mp3',
                    '11.mp3',
                    '12.mp3',
                ];
                break;
            case 'news':
                $array = [
                    '01.jpg',
                    '02.jpg',
                    '03.jpg',
                    '04.jpg',
                    '05.jpg',
                    '06.jpg',
                    '07.jpg',
                    '08.jpg',
                    '09.jpg',
                    '10.jpg',
                    '11.jpg',
                    '12.jpg',
                    '13.jpg',
                    '14.jpg',
                    '15.jpg',
                    '16.jpg',
                ];
                break;
            case 'reference':
                $array = [
                    '01.jpg',
                    '02.jpg',
                    '03.jpg',
                    '04.jpg',
                    '05.jpg',
                    '06.jpg',
                    '07.jpg',
                    '08.jpg',
                    '09.jpg',
                    '10.jpg',
                    '11.jpg',
                    '12.jpg',
                    '13.jpg',
                    '14.jpg',
                    '15.jpg',
                    '16.jpg',
                ];
                break;
            case 'doctor':
                $array = [
                    '01.jpg',
                    '02.jpg',
                ];
                break;
            case 'admin':
                $array = [
                    'MR.png',
                    'LH.jpg',
                    'WW.jpg',
                    'SB.png',
                    'AS.png',
                    'TA.jpg',
                    'AH.jpg',
                    'JB.png',
                ];
                break;
            case 'user':
                $array = [
                    '01.jpg',
                    '02.jpg',
                    '03.jpg',
                    '04.jpg',
                    '05.jpg',
                    '06.jpg',
                    '07.jpg',
                    '08.jpg',
                    '09.jpg',
                    '10.jpg',
                    '11.jpg',
                ];
                break;
            default:
                $array = [];
                throw new \Exception("No valid namespace $type in uploadFile() fixture loader.");
        }

        if ($i === -1) {
            $i = array_rand($array);
        }


        $randomFile = $array[$i];
        $originalFile = __DIR__ . '/../files/' . $type . '/' . $randomFile;
        $tmpCopyFile = __DIR__ . '/../../../../var/tmp/copy_of_' . uniqid() . $randomFile; // unique id required!!
        //$tmpCopyFile = sys_get_temp_dir() . '/' . $type . '_' . $randomFile;
        copy($originalFile, $tmpCopyFile);
        echo $type . '/' . $randomFile . '('. is_file($tmpCopyFile) .'), ';
        $uploadableFile = new UploadedFile($tmpCopyFile, $tmpCopyFile, null, null, null, true); // last has be true!!

        return $uploadableFile;
    }

    public function copyFile($type)
    {
        switch ($type) {
            case 'therapy':
                $destination = __DIR__ . '/../../../../web/library/therapies/';
                $ext = '.mp3';
                $array = [
                    'Testfile1.mp3',
                    'Testfile2.mp3',
                ];
                break;
            case 'track':
                $destination = __DIR__ . '/../../../../web/library/tracks/';
                $ext = '.mp3';
                $array = [
                    'Testfile1.mp3',
                    'Testfile2.mp3',
                ];
                break;
            default:
                throw new \Exception("No valid namespace in copyFiles() fixture loader.");
        }

        $randomFile = $array[array_rand($array)];
        $originalFile = __DIR__ . '/../files/' . $type . '/' . $randomFile;
        $copyFileName = uniqid() . $ext;
        copy($originalFile, $destination . $copyFileName);

        return $copyFileName;
    }

    public function therapyDosage()
    {
        $array = [5, 15, 20, 30];
        return $array[array_rand($array)];
    }

    public function therapyRate()
    {
        $array = [1, 2, 3, 4];
        return $array[array_rand($array)];
    }

    public function therapyCycleType()
    {
        $array = [
            'months',
            'weeks',
        ];

        return $array[array_rand($array)];
    }


    public function reference()
    {
        $array = [
            'The Soundtrack of Our Lives - The Special Case of Musical Memories',
            'Music as Medicine',
            'Keep your Brain Young with Music',
            'Listen with Chemo Tone',
            'Dyslexia',
            'Sleep - Goldberg',
            'Memory Increase',
            'Palliative Tones',
            'Intensive Care Classic',
            'Palliative Classic',
            'Palliative Tones',
        ];

        return $array[array_rand($array)];
    }


    public function disease()
    {
        $array = [
            'Addiction',
            'ADHD',
            'Alzheimers',
            'Analgesic',
            'Anti Stress',
            'Anxiety',
            'Autism',
            'Chronic Pain',
            'Circadian',
            'Creativity',
            'Dementia',
            'Depression',
            'Diabetes',
            'Dyslexia',
            'Epilepsy',
            'Focus',
            'Heart Rate',
            'Insomnia',
            'Learning Difficulties',
            'NICU',
            'Pain Reduction',
            'Parkinsons',
            'Phobia',
            'Pre and Post Operative',
            'Relaxation',
            'SAD',
            'Schizophrenia',
            'Seasonal Affective Disorder',
            'Sleep Difficulty',
            'Stress Relief',
            'Stutter',
            'Thalamus',
            'Thyroid',
            'Vigilance',
        ];

        return $array[array_rand($array)];
    }


    public function location()
    {
        $array = [
            'New York City',
            'Los Angeles',
            'Chicago',
            'Houston',
            'Philadelphia',
            'Phoenix',
            'San Antonio',
            'San Diego',
            'Dallas',
            'San José',
            'Jacksonville',
            'Indianapolis',
            'San Francisco',
            'Austin',
            'Columbus',
            'Fort Worth',
            'Charlotte',
            'Detroit',
            'El Paso',
            'Memphis',
            'Baltimore',
            'Boston',
            'Seattle',
            'Washington D.C.',
            'Nashville',
            'Denver',
            'Louisville',
            'Milwaukee',
            'Portland',
            'Las Vegas',
            'Oklahoma City',
            'Albuquerque',
            'Tucson',
            'Fresno',
        ];

        return $array[array_rand($array)];
    }


    public function zipcode()
    {
        $array = [
            '01001',
            '04563',
            '08067',
            '12768',
            '12769',
            '28463',
            '26101',
            '42130',
            '35235',
            '32697',
            '23838',
            '27925',
            '30294',
            '46001',
            '45324',
            '49235',
            '45899',
            '75835',
            '51014',
            '53465',
            '62354',
            '68748',
            '74512',
            '74321',
            '84587',
            '86451',
            '94587',
            '95478',
            '99147',
            '99770',
        ];

        return $array[array_rand($array)];
    }




    public function country()
    {
        $array = [
            'de',
            'gb',
            'an',
            'ad',
            'ae',
            'af',
            'ag',
            'ai',
            'al',
            'am',
            'ao',
            'aq',
            'ar',
            'as',
            'at',
            'au',
            'aw',
            'ax',
            'az',
            'ba',
            'bb',
            'bd',
            'be',
            'bf',
            'bg',
            'bh',
            'bi',
            'bj',
            'bl',
            'bm',
            'bn',
            'bo',
            'bq',
            'br',
            'bs',
            'bt',
            'bv',
            'bw',
            'by',
            'bz',
            'ca',
            'cc',
            'cd',
            'cf',
            'cg',
            'ch',
            'ci',
            'ck',
            'cl',
            'cm',
            'cn',
            'co',
            'cr',
            'cu',
            'cv',
            'cw',
            'cx',
            'cy',
            'cz',
            'de',
            'dj',
            'dk',
            'dm',
            'do',
            'dz',
            'ec',
            'ee',
            'eg',
            'eh',
            'er',
            'es',
            'et',
            'fi',
            'fj',
            'fo',
            'fr',
            'ga',
            'gb',
            'gd',
            'ge',
            'gf',
            'gg',
            'gh',
            'gi',
            'gl',
            'gm',
            'gn',
            'gp',
            'gq',
            'gr',
            'gs',
            'gt',
            'gu',
            'gw',
            'gy',
            'hk',
            'hm',
            'hn',
            'hr',
            'ht',
            'hu',
            'id',
            'ie',
            'il',
            'im',
            'in',
            'io',
            'iq',
            'ir',
            'is',
            'it',
            'je',
            'jm',
            'jo',
            'jp',
            'ke',
            'km',
            'kn',
            'kp',
            'kr',
            'kw',
            'ky',
            'kz',
            'la',
            'lb',
            'lc',
            'li',
            'lk',
            'lr',
            'ls',
            'lt',
            'lu',
            'lv',
            'ly',
            'ma',
            'mc',
            'md',
            'me',
            'mg',
            'mh',
            'mk',
            'ml',
            'mo',
            'mp',
            'mq',
            'ms',
            'mt',
            'mu',
            'mv',
            'mw',
            'mx',
            'my',
            'mz',
            'na',
            'nc',
            'ne',
            'ng',
            'ni',
            'nl',
            'no',
            'np',
            'nr',
            'nu',
            'nz',
            'om',
            'pa',
            'pe',
            'ph',
            'pk',
            'pl',
            'pr',
            'ps',
            'pt',
            'pw',
            'py',
            'qa',
            're',
            'ro',
            'rs',
            'ru',
            'rw',
            'se',
            'sh',
            'sz',
            'tr',
            'ua',
            'us',
            'vi',
            'za',
        ];

        return $array[array_rand($array)];
    }

    public function medicalScience($i = null)
    {
        $array = [
            'aetiology',
            'biomedicine',
            'bioengineering',
            'cytology',
            'dentistry',
            'dietetics',
            'epidemiology',
            'genetics',
            'haematology',
            'immunology',
            'medicine',
            'neurology',
            'neuroscience',
            'ophthalmology',
            'pathology',
            'radiology',
            'urology',
            'virology',
        ];

        return isset($array[$i]) ? $array[$i] : $array[array_rand($array)];
    }
}