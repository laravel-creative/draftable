<?php

namespace LaravelCreative\Draftable\traits;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use LaravelCreative\Draftable\Draftable;
use Mockery\Exception;

trait DraftableModel
{


    private static $userID;

    /**
     * get All Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getAllDrafts($unfillable = false)
    {

        $draftsEnteries = static::DraftsQuery()->get();
        return static::getDraftsCollection($draftsEnteries, $unfillable);
    }


    /**
     * get All Published Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getPublishedDraft($unfillable = false)
    {
        $draftsEnteries = static::DraftsQuery()->Published()->get();
        return static::getDraftsCollection($draftsEnteries, $unfillable);
    }


    /**
     * get All UnPublished Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getUnPublishedDraft($unfillable = false)
    {
        $draftsEnteries = static::DraftsQuery()->UnPublished()->get();
        return static::getDraftsCollection($draftsEnteries, $unfillable);
    }


    /**
     * Get Drafts Collection
     * @param $draftsEnteries
     * @param $unfillable
     * @return Collection
     */
    private static function getDraftsCollection($draftsEnteries, $unfillable)
    {
        return static::buildCollection($draftsEnteries, $unfillable);
    }


    /**
     * Save model as draft
     * @return $this
     */
    public function saveAsDraft()
    {
        $draftableArray = $this->toArray();
        $draftableEnteryArray = ['draftable_id' => $this->id, 'draftable_data' => $draftableArray, 'draftable_model' => static::class, 'published_at' => null,'user_id'=>static::$userID,'data'=>[]];
        try {
            $draft=Draftable::create($draftableEnteryArray);
            $this->draft=$draft;
        } catch (\Exception $e) {
            throw new  Exception($e->getMessage());
        }
        return $this;
    }


    /**
     * Save model with draft
     * @return $this
     */
    public function saveWithDraft()
    {
        $this->save();
        $draftableArray = $this->toArray();
        unset($draftableArray['id']);
        $draftableEnteryArray = ['draftable_id' => $this->id, 'draftable_data' => $draftableArray, 'draftable_model' => static::class, 'published_at' => Carbon::now(),'user_id'=>static::$userID,'data'=>[]];
        try {
            $draft=Draftable::create($draftableEnteryArray);
            $this->draft=$draft;
        } catch (\Exception $e) {
            throw new  Exception($e->getMessage());
        }
        return $this;
    }


    /**
     * Build Collection for model
     * @param $draftsEnteries
     * @param bool $unfillable
     * @return Collection
     */
    private static function buildCollection($draftsEnteries, $unfillable = false)
    {
        if ($unfillable) return $draftsEnteries;
        $collection = new Collection();
        foreach ($draftsEnteries as $entery) {
            $new_class = new static();
            $new_class->forceFill($entery->draftable_data);
            $new_class->published_at = $entery->published_at;
            $new_class->draft = $entery;
            $collection->push($new_class);
        }
        return $collection;
    }


    /**
     * Drafts Main Query
     * @return mixed
     */
    private static function DraftsQuery()
    {
        $userQuery=[];
        if(Static::$userID!=null)$userQuery['user_id']=Static::$userID;

        return Draftable::where('draftable_model', static::class)->where($userQuery);
    }


    /**
     * Publish unpublished draft
     * @return $this
     */
    public function publish()
    {
        if (is_null($this->published_at)) {
            $this->draft->publish();
        }
        return $this;
    }


    /**
     * Drafts morph relation
     * @return mixed
     */
    public function drafts()
    {
        return $this->morphMany(Draftable::class, 'draftable', 'draftable_model', 'draftable_id');
    }


    /**
     * get draft by id
     * @param $id
     * @return mixed
     */
    public function getDraft($id)
    {
        $draftsEnteries = static::DraftsQuery()->where('id', $id)->first();
        return $draftsEnteries;
    }


    /**
     * Set user for draft ( the creator of draft )
     * @param $user
     * @return DraftableModel
     */
    public static  function setUser($user)
    {
        static::$userID = $user->id;
        return new static();
    }


}
