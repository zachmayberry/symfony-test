<?php


namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PlaylistRepository")
 * @ORM\Table(name="playlist")
 * @ORM\HasLifecycleCallbacks
 */
class Playlist
{
    const TYPE_TONE = 1;
    const TYPE_MUSIC = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     */
    private $title = '';

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PlaylistTrack", mappedBy="playlist", cascade={"remove", "persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"sorting" = "asc"})
     */
    private $playlistTracks;


    public function __construct()
    {
        $this->playlistTracks = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = (int)$type;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getPlaylistTracks()
    {
        return $this->playlistTracks;
    }

    /**
     * @param mixed $playlistTracks
     */
    public function setPlaylistTracks($playlistTracks)
    {
        $this->playlistTracks = $playlistTracks;
    }

    /**
     * @return mixed
     */
    public function clearPlaylistTracks()
    {
        return $this->getPlaylistTracks()->clear();
    }


    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $date = new \DateTime();

        if (!$this->createdAt) {
            $this->createdAt = $date;
        }

        $this->updatedAt = $date;
    }

    /**
     * Compare a playlist with playlist form data and return if they have the same contents
     *
     * @param Playlist $oldPlaylist
     * @param array $newPlaylistFormData
     * @param array|null $binauralSettings
     * @return bool
     */
    public static function comparePlaylistWithFormData(Playlist $oldPlaylist = null, $newPlaylistFormData, $binauralSettings = null)
    {
        if (null === $oldPlaylist) {
            return true;
        }

        $playlistType = $oldPlaylist->getType();

        $newPlaylistLength = is_array($newPlaylistFormData) ? count($newPlaylistFormData) : 0;

        // check if tracks count are equal
        $hasChanges = count($oldPlaylist->getPlaylistTracks()) !== $newPlaylistLength;

        // if tracks count are equal, we compare all tracks inside the playlist
        if (!$hasChanges) {

            foreach($newPlaylistFormData as $key => $trackId) {

                /** @var PlaylistTrack|null $oldPlaylistTrack */
                $oldPlaylistTrack = $oldPlaylist->getPlaylistTracks()->get($key);

                // quit if key not in old playlist
                if (null === $oldPlaylistTrack) {
                    $hasChanges = true;
                    break;
                }

                if (!$oldPlaylistTrack->getTrack() || (int)$trackId !== $oldPlaylistTrack->getTrack()->getId()) {
                    $hasChanges = true;
                    break;
                }

                // if is binaural playlist, also check the tone duration from binauralPlaylistSettings
                if ($playlistType === Playlist::TYPE_TONE && $binauralSettings !== null) {

                    if (!isset($binauralSettings[$key], $binauralSettings[$key]['duration'])
                        || $oldPlaylistTrack->getDuration() !== $binauralSettings[$key]['duration'] * 60) {
                        $hasChanges = true;
                        break;
                    }
                }
            }
        }

        return $hasChanges;
    }

    /**
     * Deep clone with playlist tracks
     */
    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);

            // cloning the playlistTracks
            $playlistTracksClone = new ArrayCollection();
            foreach ($this->playlistTracks as $playlistTrack) {
                $playlistTrackClone = clone $playlistTrack;
                $playlistTrackClone->setPlaylist($this);
                $playlistTracksClone->add($playlistTrackClone);
            }
            $this->playlistTracks = $playlistTracksClone;
        }
    }
}