<?php
/**
 * Tag file  
 * 
 * PHP version 5
 * 
 * @category Git
 * 
 * @package Backend
 * 
 * @author EISGE <kalifast@eisge.com>
 * 
 * @license kalifast.com Kalifast
 * 
 * @version SVN: $Id$
 * 
 * @link kalifast.com
 */ 

/**
 * Tag class 
 * 
 * @category Git
 * 
 * @package Backend
 * 
 * @author EISGE <kalifast@eisge.com>
 * 
 * @license kalifast.com Kalifast
 * 
 * @link kalifast.com
 */
class Git extends BaseApi
{
    /**
     * Ajouter un repo git
     * 
     * @return array
     */
    function addGitRepo()
    {
        $d = $this->checkParams(
            [
                'repo_name' => 'string',
                'repo_url' =>'string'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT max(ei_git_repo_id+1)FROM ei_git_repo;"
        );
        $s->execute(
            [
            ]
        );
        $max_git_id = (int)($s->fetch()?:[0])[0];
        // error_log(json_encode($max_git_id));
        if ($max_git_id == 0) {
            $max_git_id = 1;
        }
        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_repo` (`ei_git_repo_id`, `ei_git_repo_name`,`ei_git_repo_url`) VALUES (:max_git_id, :repo_name, :repo_url)"
        );
        $s->execute(
            [
                'repo_name' => $d->repo_name,
                'repo_url' => $d->repo_url,
                'max_git_id' => $max_git_id,
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                MAX(ei_brick_tree_node_id) + 1 AS max_ei_brick_tree_node_id,
                MAX(position) + 1 AS max_position
            FROM
                ei_git_file_brick_tree;"
        );
        $s->execute([]);
        $max_data = $s->fetch(PDO::FETCH_ASSOC);


        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_file_brick_tree` (`ref_object_familly_id`, `ei_brick_tree_parent_node_id`, `ei_brick_tree_node_id`, `position`, `foldername`, `ei_git_brick_id`, `showed`,ei_git_id) 
            VALUES ('BRK', '1', :ei_brick_tree_node_id, :max_position, :repo_name, null, 'Y',:ei_git_id);"
        );
        $s->execute(
            [
                'repo_name' => $d->repo_name,
                'ei_brick_tree_node_id' => $max_data['max_ei_brick_tree_node_id'] ,
                'max_position' => $max_data['max_position'] ,
                'ei_git_id'=>$max_git_id
            ]
        );
        
        return true;
    }

    /**
     * Ajouter les branch poru le repo git
     * 
     * @return array
     */
    function addGitRepoBranch()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
                'ei_git_repo_branch_name' => 'string',
                'ei_git_repo_branch_type' => 'string',
                'ei_git_repo_branch_id' => 'int'
            ]
        );
        $s = $this->PDO->prepare(
            "SELECT max(ei_git_repo_trunk_id+1)FROM ei_git_repo_trunk;"
        );
        $s->execute(
            [
            ]
        );
        $ei_git_repo_branch_id = (int)($s->fetch()?:[0])[0];
        if ($ei_git_repo_branch_id == 0) {
            $ei_git_repo_branch_id = 1;
        }
        $s = $this->PDO->prepare(
            "INSERT IGNORE INTO `ei_git_repo_trunk` 
            (`ei_git_repo_id`, `trunk_name`, `ei_git_repo_trunk_id`, `trunk_type`) 
            VALUES (:repo_id, :ei_git_repo_branch_name, :max_branch_id, :ei_git_repo_branch_type);"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
                'ei_git_repo_branch_name' => $d->ei_git_repo_branch_name,
                'ei_git_repo_branch_type' => $d->ei_git_repo_branch_type,
                'max_branch_id' => $ei_git_repo_branch_id,
            ]
        );
        
        return true;
    }

    /**
     * Update les branch poru le repo git
     * 
     * @return array
     */
    function updateGitRepoBranch()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
                'ei_git_repo_branch_name' => 'string',
                'ei_git_repo_branch_type' => 'string',
                'ei_git_repo_branch_id' => 'int'
            ]
        );
        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_trunk` SET `trunk_type`=:ei_git_repo_branch_type 
            WHERE `ei_git_repo_id`=:repo_id and`ei_git_repo_trunk_id`=:max_branch_id and`trunk_name`=:ei_git_repo_branch_name"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
                'ei_git_repo_branch_name' => $d->ei_git_repo_branch_name,
                'ei_git_repo_branch_type' => $d->ei_git_repo_branch_type,
                'max_branch_id' => $d->ei_git_repo_branch_id,
            ]
        );
        
        return true;
    }

    /**
     *  Recuperer les user assign 
     * 
     * @return array
     */
    function getGitUserToKalifastUser()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'user_email' => 'string'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT ei_user_id FROM ei_git_repo_contributor where ei_git_repo_id=:ei_git_repo_id and contributor_mail=:user_email;"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'user_email' => $d->user_email
            ]
        );

        $userId = (int)($s->fetch()?:[0])[0];
        // error_log($userId);
        // error_log($userId);


        if ($userId != 0) {
            $user = $this->callClass(
                "Core", 
                "getUserInformation", 
                [
                    'ei_user_id' => $userId 
                    
                ]
            );

            // error_log(json_encode($user->getdata()));
            $this->setData($user->getdata());
        }
        
      
        
        return true;
    }

    /**
     *  Recuperer les user en bdd en fonction du gitid 
     * 
     * @return array
     */
    function getGitUserRepoId()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_contributor egrc
                    LEFT OUTER JOIN
                ei_user eu ON eu.email = egrc.ei_user_email
            WHERE
                egrc.ei_git_repo_id = :ei_git_repo_id"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id
            ]
        );

        $userList = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($userList);
      
        
        return true;
    }

    /**
     *  Assigner un user id a un user git
     * 
     * @return array
     */
    function assignGitUserToKalifastUser()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_user_id' => 'int',
                'contributor_mail'=> 'string'
            ]
        );

        $id = $this->callClass(
            "Core", 
            "getUserInformation", 
            [
                'ei_user_id' => $d->ei_user_id 
                
            ]
        );
        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_contributor` SET `ei_user_id`=:ei_user_id, `ei_user_email`=:ei_user_mail WHERE `contributor_mail`=:contributor_mail and`ei_git_repo_id`=:ei_git_repo_id"
            
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_user_id' => $d->ei_user_id,
                'contributor_mail' => $d->contributor_mail,
                'ei_user_mail' => $id->getdata()['email']
            ]
        );
        
        return true;
    }

    /**
     *  Ajouter les user Git Sur un repo
     * 
     * @return array
     */
    function AddGitUserRepo()
    {
        $d = $this->checkParams(
            [
                'contributorArray' => 'array',
                'ei_git_repo_id'=> 'int'
            ]
        );

        foreach ($d->contributorArray as $key => $value) {

            $s = $this->PDO->prepare(
                "INSERT IGNORE INTO `ei_git_repo_contributor` (`ei_git_repo_id`, `ei_user_id`, `contributor_name`, `contributor_mail`, `ei_user_email`) 
                VALUES (:ei_git_repo_id, 0, :contributor_name, :contributor_mail, '');"
            );
            $s->execute(
                [
                    'ei_git_repo_id' => $d->ei_git_repo_id,
                    'contributor_name' => $value->name,
                    'contributor_mail' => $value->mail
                ]
            );

            // verifier si un user existe en bdd avec le meme mail si c'est le cas alors l'assigner directement

            $s = $this->PDO->prepare(
                "SELECT 
                    *
                FROM
                    ei_user
                WHERE
                    email = :contributor_mail"
            );
            $s->execute([
                'contributor_mail' => $value->mail
            ]);

            $userCompareContributorMail = $s->fetch(PDO::FETCH_ASSOC);
            if ($userCompareContributorMail) {

                 $this->callClass(
                    "Git", 
                    "assignGitUserToKalifastUser", 
                    [
                        'ei_git_repo_id' => $d->ei_git_repo_id,
                        'ei_user_id' => $userCompareContributorMail['ei_user_id'],
                        'contributor_mail'=> $value->mail
                    ]
                );
            }

        }
        
        return true;
    }



    /**
     *  Ajouter les branch d'un repo git
     * 
     * @return array
     */
    function AddGitBranchRepo()
    {
        $d = $this->checkParams(
            [
                'branchArray' => 'array',
                'ei_git_repo_id'=> 'int'
            ]
        );

        foreach ($d->branchArray as $key => $value) {

            $s = $this->PDO->prepare(
                "SELECT max(ei_git_repo_subject_branch_id)+1 FROM ei_git_repo_subject_branch;"
            );
            $s->execute([]);

            $max_branch_id = (int)($s->fetch()?:[0])[0];
            if($max_branch_id === 0){
                $max_branch_id = 1;
            } 


            $s = $this->PDO->prepare(
                "SELECT 
                    COUNT(1) AS count
                FROM
                    ei_git_repo_subject_branch
                WHERE
                    ei_git_repo_id = :ei_git_repo_id
                        AND subject_branch_name = :repo_name_branch
                        AND last_commit_id = :last_commit_id"
            );
            $s->execute([
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'repo_name_branch' => $value->repo_name_branch,
                'last_commit_id' => $value->last_commit->commit_name
            ]);

            $count_exist = (int)($s->fetch()?:[0])[0];

            // error_log($count_exist);
            
            if ($count_exist === 0) {
               $s = $this->PDO->prepare(
                    "INSERT IGNORE INTO `ei_git_repo_subject_branch` (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_git_repo_trunk_parent_id`, `subject_branch_name`, `ei_subject_id`,`last_commit_id`,`last_commit_message`)
                    VALUES (:ei_git_repo_id, :max_branch_id, 0, :repo_name_branch, 0,:last_commit_id,:last_commit_message);"
                );
                $s->execute(
                    [
                        'ei_git_repo_id' => $d->ei_git_repo_id,
                        'repo_name_branch' => $value->repo_name_branch,
                        'max_branch_id' => $max_branch_id,
                        'last_commit_id' => $value->last_commit->commit_name,
                        'last_commit_message' => $value->last_commit->message
                    ]
                ); 
            }
            
        }
        
        return true;
    }


    /**
     * Connecter un subject a une branch git
     * 
     * @return array
     */
    function assignSubjectToBranch()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_git_repo_subject_branch_id' => 'int',
                'ei_subject_id' => 'int'
            ]
        );

        $d = $this->initOptionalParams('branch_type', 'string', '');

        if ($d->branch_type) {
            $s = $this->PDO->prepare(
                "UPDATE `ei_git_repo_subject_branch` 
                SET 
                    `branch_type` = :branch_type
                WHERE
                    `ei_git_repo_subject_branch_id` = :ei_git_repo_subject_branch_id
                        AND `ei_git_repo_id` = :ei_git_repo_id
                "
            );
            $s->execute(
                [
                    'ei_git_repo_id' => $d->ei_git_repo_id,
                    'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id, 
                    'branch_type' => $d->branch_type
                ]
            );
        } else {
            $s = $this->PDO->prepare(
                "UPDATE `ei_git_repo_subject_branch` 
                SET 
                    `ei_subject_id` = :ei_subject_id
                WHERE
                    `ei_git_repo_subject_branch_id` = :ei_git_repo_subject_branch_id
                        AND `ei_git_repo_id` = :ei_git_repo_id
                "
            );
            $s->execute(
                [
                    'ei_git_repo_id' => $d->ei_git_repo_id,
                    'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                    'ei_subject_id' => $d->ei_subject_id
                ]
            );
        }

        


        
        return true;
    }


    /**
     * Ajouter un lien entre une brick et un subject
     * 
     * @return array
     */
    function addSubjectBrickLink()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int',
                'ei_subject_id' => 'int'
            ]
        );
        
        $s = $this->PDO->prepare(
            "SELECT max(ei_git_subject_brick_link_id)+1 FROM ei_git_subject_brick_link;"
        );
        $s->execute([]);

        $max_subject_brick_link_id = (int)($s->fetch()?:[0])[0];
        if($max_subject_brick_link_id === 0){
            $max_subject_brick_link_id = 1;
        }

        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_subject_brick_link` (`ei_git_subject_brick_link_id`, `ei_git_brick_id`, `ei_subject_id`) VALUES (:max_subject_brick_link_id, :ei_git_brick_id, :ei_subject_id);"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id,
                'ei_subject_id' => $d->ei_subject_id,
                'max_subject_brick_link_id' => $max_subject_brick_link_id
            ]
        );
        
        return true;
    }

    /**
     * Recuperer les file brick rattacher a un subject
     * 
     * @return array
     */
    function getGitFileBrickSubject()
    {
        $d = $this->checkParams(
            [
                'ei_subject_id' => 'int'
            ]
        );
        $s = $this->PDO->prepare(
            "WITH MaxCommit AS (
                SELECT 
                    egsbl.ei_git_brick_id,
                    MAX(egrsc.commit_datetime) AS latest_commit_datetime
                FROM
                    ei_git_subject_brick_link egsbl
                        INNER JOIN
                    ei_git_repo_subject_commit_git_brick egrscgb ON egrscgb.ei_git_brick_id = egsbl.ei_git_brick_id
                        INNER JOIN
                    ei_git_repo_subject_commit egrsc ON egrscgb.ei_commit_id = egrsc.ei_commit_id
                WHERE
                    egsbl.ei_subject_id = :ei_subject_id
                GROUP BY 
                    egsbl.ei_git_brick_id
            )
            SELECT DISTINCT
                egfb.ei_git_brick_name,
                egsbl.ei_git_brick_id,
                egrsc.commit_user_id as created_by,
                egrsc.commit_datetime as created_at,
                mc.latest_commit_datetime AS updated_at,
                eu.picture_path as created_by_picture_path
            FROM
                ei_git_subject_brick_link egsbl
                    INNER JOIN
                ei_git_file_brick egfb ON egfb.ei_git_brick_id = egsbl.ei_git_brick_id
                    INNER JOIN
                ei_git_repo_subject_commit_git_brick egrscgb ON egrscgb.ei_git_brick_id = egsbl.ei_git_brick_id
                    INNER JOIN
                ei_git_repo_subject_commit egrsc ON egrscgb.ei_commit_id = egrsc.ei_commit_id
                    INNER JOIN
                ei_user eu ON eu.ei_user_id = egrsc.commit_user_id
                    INNER JOIN
                MaxCommit mc ON mc.ei_git_brick_id = egsbl.ei_git_brick_id AND mc.latest_commit_datetime = egrsc.commit_datetime
            WHERE
                egsbl.ei_subject_id = :ei_subject_id;
            "
        );
        $s->execute(
            [
                'ei_subject_id' => $d->ei_subject_id,
            ]
        );

        $file_brick_subject_list = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($file_brick_subject_list as $key => $value) {
            if ($value['ei_git_brick_id']) {
               $obj = $this->callClass(
                "Git", 
                "getBrickPath", 
                    [
                        'ei_git_brick_id' => $value['ei_git_brick_id']
                    ]
                ); 
                $path =$obj->getData();
                $file_brick_subject_list[$key]['path'] = array_reverse($path);
            }
            

            
        }

        $this->setData($file_brick_subject_list);
        
        return true;
    }

    /**
     * Supprimer les brick connecter a un subject
     * 
     * @return array
     */
    function deleteBrickConnectedSubject()
    {
        $d = $this->checkParams(
            [
                'ei_subject_id' => 'int',
                'ei_git_brick_id' => 'int'
            ]
        );
        $s = $this->PDO->prepare(
            " DELETE FROM `ei_git_subject_brick_link` 
            WHERE
                `ei_git_brick_id` = :ei_git_brick_id
                AND ei_subject_id = :ei_subject_id"
        );
        $s->execute(
            [
                'ei_subject_id' => $d->ei_subject_id,
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );
        
        return true;
    }

    /**
     * Recuperer les branch poru le repo git
     * 
     * @return array
     */
    function getGitRepoBranch()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
            ]
        );
        $s = $this->PDO->prepare(
            "SELECT  
            ei_git_repo_id,
            ei_git_repo_trunk_id,
            trunk_type,
            trunk_name as repo_name_branch  
            FROM ei_git_repo_trunk 
            where ei_git_repo_id=:repo_id 
            and LENGTH(trunk_type) >=2 ORDER BY trunk_type ASC"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
            ]
        );

        $repo_branch_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($repo_branch_list);
        
        return true;
    }

    /**
     * Recuperer quelques infos avec un ei_git_repo_id
     * 
     * @return array
     */
    function getGitRepoInfo()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int'
            ]
        );
        $s = $this->PDO->prepare(
            "SELECT * FROM ei_git_repo where ei_git_repo_id=:repo_id"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id 
            ]
        );
        $getGitRepoInfo = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($getGitRepoInfo);
        
        return true;
    }




    /**
     * Recuperer toutes les branchs pour le repo git
     * 
     * @return array
     */
    function getGitAllRepoBranch()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
            ]
        );
        $s = $this->PDO->prepare(
            "SELECT  ei_git_repo_id,ei_git_repo_trunk_id,trunk_type,trunk_name as repo_name_branch  FROM ei_git_repo_trunk where ei_git_repo_id=:repo_id"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
            ]
        );

        $repo_branch_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($repo_branch_list);
        
        return true;
    }

    /**
     * Recuperer toutes les branch du repo git
     * 
     * @return array
     */
    function getGitRepoAllBranch()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
            ]
        );
        $s = $this->PDO->prepare(
            "SELECT 
                delivery_branch_name AS repo_branch_name_list
            FROM
                ei_git_repo_delivery_branch
            WHERE
                ei_git_repo_id = :repo_id
            UNION SELECT 
                subject_branch_name AS repo_branch_name_list
            FROM
                ei_git_repo_subject_branch
            WHERE
                ei_git_repo_id = :repo_id"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
            ]
        );

        $repo_branch_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($repo_branch_list);
        
        return true;
    }

    /**
     * Recuperer liste ds repos git
     * 
     * @return array
     */
    function getGitRepoList()
    {
        $d = $this->checkParams([]);

        $s = $this->PDO->prepare(
            "SELECT * FROM ei_git_repo;"
        );
        $s->execute([]);

        $repo_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Recuperer le subjectId avec la currentbranch du service 
     * 
     * @return array
     */
    function getGitRepoCurrentBranchSubject()
    {
        $d = $this->checkParams([
            'ei_git_repo_id' => 'int',
            'subject_branch_name' => 'stringgfdfdfg'
        ]);

        $s = $this->PDO->prepare(
            "SELECT 
                ei_subject_id dfgdfghdfhdf
            FROM
                ei_git_repo_subject_branch sgdfgddf
            WHERE
                ei_git_repo_id = :ei_git_repo_id
                    AND subject_branch_name = :subject_branch_name LIMIT 1"
        );
        $s->execute([
            'ei_git_repo_id' => $d->ei_git_repo_id,
            'subject_branch_name' => $d->subject_branch_name
        ]);

        $repo_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Recuperer le nom de la branch en fonction du subject id et du repoId
     * 
     * @return array
     */
    function getGitSubjectRepoBranchCreated()
    {
        $d = $this->checkParams([
            'ei_subject_id' => 'int',
            'ei_git_repo_id' => 'int'
        ]);

        $s = $this->PDO->prepare(
            " SELECT 
                *
            FROM
                ei_git_repo_subject_branch
            WHERE
                ei_subject_id = :ei_subject_id
                AND ei_git_repo_id = :ei_git_repo_id
            "
        );
        $s->execute([
            'ei_subject_id' => $d->ei_subject_id,
            'ei_git_repo_id' => $d->ei_git_repo_id
        ]);

        $subjectRepoBranch = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($subjectRepoBranch);
        
        return true;
    }

    /**
     * Recuperer un repo git
     * 
     * @return array
     */
    function getGitRepoById()
    {
        $d = $this->checkParams(
            [
            'repo_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT * FROM ei_git_repo where ei_git_repo_id=:repo_id;"
        );
        $s->execute(
            [
                'repo_id' => $d->repo_id,
            ]
        );

        $repo_data = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($repo_data);
        
        return true;
    }

    /**
     * Recuperer le amx id repo git
     * 
     * @return array
     */
    function getGitRepoMaxId()
    {
        $d = $this->checkParams([]);

        $s = $this->PDO->prepare(
            "SELECT max(ei_git_repo_id)+1 as ei_git_repo_id FROM ei_git_repo;"
        );
        $s->execute([]);

        $max_id = (int)($s->fetch()?:[0])[0];
        if ($max_id == 0 || $max_id == null) {
            $max_id = 1;
        }

        $this->setData($max_id);
        
        return true;
    }

    /**
     * Recuperer branch type
     * 
     * @return array
     */
    function getGitRepoBranchType()
    {
        $d = $this->checkParams(
            [
            'repo_id' => 'int',
            'branch_type' => 'string'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_trunk egrb
                left outer join ei_git_repo egr on egr.ei_git_repo_id = egrb.ei_git_repo_id
            WHERE
                egrb.ei_git_repo_id = :repo_id
                    AND egrb.trunk_type = :branch_type"
        );
        $s->execute(
            [
            'repo_id' => $d->repo_id,
            'branch_type' => $d->branch_type
            ]
        );

        $repo_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Ajouter une branch au repo git
     * 
     * @return array
     */
    function addGitRepoSubjectBranch()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_git_repo_branch_id' => 'int',
                'ei_git_repo_subject_branch_name' => 'string',
                'ei_subject_id' => 'int',
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT max(ei_git_repo_subject_branch_id+1)FROM ei_git_repo_subject_branch;"
        );
        $s->execute(
            [
            ]
        );
        $max_git_id = (int)($s->fetch()?:[0])[0];
        // error_log(json_encode($max_git_id));
        if ($max_git_id == 0) {
            $max_git_id = 1;
        }
        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_repo_subject_branch` (`ei_git_repo_subject_branch_id`, `ei_git_repo_id`, `ei_git_repo_trunk_parent_id`, `subject_branch_name`,`ei_subject_id`)
            VALUES (:max_git_id, :ei_git_repo_id, :ei_git_repo_branch_id, :ei_git_repo_subject_branch_name, :ei_subject_id);"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_git_repo_branch_id' => $d->ei_git_repo_branch_id,
                'ei_git_repo_subject_branch_name' => $d->ei_git_repo_subject_branch_name,
                'ei_subject_id' => $d->ei_subject_id,
                'max_git_id' => $max_git_id,
            ]
        );
        
        return true;
    }

    /**
     * Ajouter une branch au repo git via delivery
     * 
     * @return array
     */
    function addGitRepoDeliveryBranch()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_git_repo_branch_id' => 'int',
                'ei_git_repo_delivery_branch_name' => 'string',
                'ei_delivery_id' => 'int',
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT max(ei_git_repo_delivery_branch_id+1)FROM ei_git_repo_delivery_branch;"
        );
        $s->execute(
            [
            ]
        );
        $max_git_id = (int)($s->fetch()?:[0])[0];
        // error_log(json_encode($max_git_id));
        if ($max_git_id == 0) {
            $max_git_id = 1;
        }
        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_repo_delivery_branch` (`ei_git_repo_delivery_branch_id`, `ei_git_repo_id`, `ei_git_repo_trunk_parent_id`, `delivery_branch_name`,`ei_delivery_id`)
            VALUES (:max_git_id, :ei_git_repo_id, :ei_git_repo_branch_id, :ei_git_repo_delivery_branch_name, :ei_delivery_id);"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_git_repo_branch_id' => $d->ei_git_repo_branch_id,
                'ei_git_repo_delivery_branch_name' => $d->ei_git_repo_delivery_branch_name,
                'ei_delivery_id' => $d->ei_delivery_id,
                'max_git_id' => $max_git_id,
            ]
        );
        
        return true;
    }


    /**
     * Supprimer une function relier a la brick
     * 
     * @return array
     */
    function deleteFunctionBrickId()
    {
        $d = $this->checkParams([
            'ei_function_id' => 'int',
            'ei_brick_id' => 'int'
        ]);

        $s = $this->PDO->prepare(
            "DELETE FROM `ei_git_file_brick_function` WHERE `ei_git_brick_id`=:ei_brick_id and`ei_function_id`=:ei_function_id"
        );
        $s->execute([
            'ei_brick_id' => $d->ei_brick_id,
            'ei_function_id' => $d->ei_function_id
        ]);
        return true;
    }

    
    /**
     * Verifier si la branch du subject existe deja en fonction du repo selectionner
     * 
     * @return array
     */
    function getGitRepoSubjectBranch()
    {
        $d = $this->checkParams(
            [
            'ei_git_repo_id' => 'int',
            'ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_subject_branch
            WHERE
                ei_subject_id = :ei_subject_id
                    AND ei_git_repo_id = :ei_git_repo_id"
        );
        $s->execute(
            [
            'ei_git_repo_id' => $d->ei_git_repo_id,
            'ei_subject_id' => $d->ei_subject_id
            ]
        );

        $repo_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Recuperer toutes les branch d'un repo 
     * 
     * @return array
     */
    function getGitRepoBranchList()
    {
        $d = $this->checkParams(
            [
            'ei_git_repo_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                egrsb.*, es.ei_delivery_id, ed.delivery_name
            FROM
                ei_git_repo_subject_branch egrsb
                    LEFT JOIN
                ei_subject es ON egrsb.ei_subject_id = es.ei_subject_id
                    AND ei_subject_version_id = (SELECT 
                        MAX(ei_subject_version_id)
                    FROM
                        ei_subject
                    WHERE
                        ei_subject_id = es.ei_subject_id)
                    LEFT JOIN
                ei_delivery ed ON es.ei_delivery_id = ed.ei_delivery_id
            WHERE
                ei_git_repo_id = :ei_git_repo_id"
        );
        $s->execute(
            [
            'ei_git_repo_id' => $d->ei_git_repo_id
            ]
        );

        $repo_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Verifier si la branch de la delivery existe deja en fonction du repo selectionner
     * 
     * @return array
     */
    function getGitRepoDeliveryBranch()
    {
        $d = $this->checkParams(
            [
            'ei_git_repo_id' => 'int',
            'ei_delivery_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_delivery_branch
            WHERE
                ei_delivery_id = :ei_delivery_id
                    AND ei_git_repo_id = :ei_git_repo_id"
        );
        $s->execute(
            [
            'ei_git_repo_id' => $d->ei_git_repo_id,
            'ei_delivery_id' => $d->ei_delivery_id
            ]
        );

        $repo_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($repo_list);
        
        return true;
    }

    /**
     * Recuperer liste des branches en fonction du repo par rapport au subject
     * 
     * @return array
     */
    function getGitSubjectRepoBranch()
    {
        $d = $this->checkParams(
            [
                'ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                egrsb.ei_git_repo_id as git_id,egrsb.*, egr.*, egrb.*, es.ei_delivery_id, ed.delivery_name,COALESCE(egrsc.commit_merge_datetime,null) AS lastMergeDatetime
            FROM
            ei_git_repo_subject_branch egrsb
                INNER JOIN
            ei_git_repo egr ON egr.ei_git_repo_id = egrsb.ei_git_repo_id
                INNER JOIN
            ei_subject es ON egrsb.ei_subject_id = es.ei_subject_id
                INNER JOIN
            ei_delivery ed ON es.ei_delivery_id = ed.ei_delivery_id
                left JOIN
            ei_git_repo_trunk egrb ON egrb.ei_git_repo_trunk_id = egrsb.ei_git_repo_trunk_parent_id
                AND egrb.ei_git_repo_id = egrsb.ei_git_repo_id
                LEFT JOIN
            (SELECT 
                commit_ei_subject_id,
                    MAX(commit_merge_datetime) AS commit_merge_datetime
            FROM
                ei_git_repo_subject_commit
            GROUP BY commit_ei_subject_id) egrsc ON egrsc.commit_ei_subject_id = egrsb.ei_subject_id
            WHERE
                egrsb.ei_subject_id =:ei_subject_id AND es.ei_subject_version_id = (SELECT 
            MAX(ei_subject_version_id)
        FROM
            ei_subject
        WHERE
            ei_subject_id = :ei_subject_id)"
        );
        $s->execute(
            [
                'ei_subject_id' => $d->ei_subject_id
            ]
        );

        $branch_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $grouped_data = array();

        foreach ($branch_list as $key => $value) {
 
            $data = $this->callClass(
                "Git", 
                "getGitIntegrationBranchWithRepoAndSubject", 
                [
                    'ei_git_repo_id' => $value['git_id'],
                    'ei_delivery_id' => $value['ei_delivery_id']
                ]
            );
            $tempdata = $data->getdata();

        $s = $this->PDO->prepare(
            "SELECT 
            CONCAT('[',
                GROUP_CONCAT(DISTINCT CONCAT('{\"ei_commit_id\":\"',
                            e1.ei_commit_id,
                            '\",\"commit_message\":\"',
                            e1.commit_message,
                            '\",\"commit_user_id\":\"',
                            e1.commit_user_id,
                            '\",\"commit_datetime\":\"',
                            e1.commit_datetime,
                            '\",\"ei_git_repo_subject_branch_id\":\"',
                            e1.ei_git_repo_subject_branch_id,
                            '\",\"ei_git_repo_id\":\"',
                            e1.ei_git_repo_id,
                            '\",\"commit_ei_subject_id\":\"',
                            e1.commit_ei_subject_id,
                            '\"}')),
                ']') AS commit_json
            FROM
                ei_git_repo_subject_commit e1
            WHERE
                e1.ei_git_repo_id = :ei_git_repo_id
                    AND e1.ei_git_repo_subject_branch_id = :ei_git_repo_subject_branch_id;"
            );
            $s->execute(
                [
                    'ei_git_repo_id' => $value['ei_git_repo_id'],
                    'ei_git_repo_subject_branch_id' => $value['ei_git_repo_subject_branch_id']
                ]
            );

            $data = $s->fetch(PDO::FETCH_ASSOC);

            $value['CommitList'] = $data;

            $subject_id = $value['ei_subject_id'];
            $repo_id = $value['ei_git_repo_id'];


            if(isset($tempdata['delivery_branch_name'])) {
                $value['branch_integration'] = true;
                $value['branch_to_name'] = $tempdata['delivery_branch_name'];
            } else {
                $value['branch_integration'] = false;
                $value['branch_to_name'] = '';
            }
            

            $repo_id = $value['ei_git_repo_id'];
            $repo_name = $value['ei_git_repo_name'];
            
            if (!isset($grouped_data[$repo_id])) {
                $grouped_data[$repo_id] = array(
                    'ei_git_repo_id' => $repo_id,
                    'ei_git_repo_name' => $repo_name,
                    'items' => array()
                );
            }
            
            
            // Ajoutez les autres informations à la liste des items
            $grouped_data[$repo_id]['items'][] = $value;
        }

        $this->setData($grouped_data);
        
        return true;
    }

    
    /**
     * Recuperer liste des commits sur la branch undefined d'un subject
     * 
     * @return array
     */
    function getGitSubjectUndefinedRepoBranch()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int', 
                'commit_ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
            CONCAT('[',
                GROUP_CONCAT(DISTINCT CONCAT('{\"ei_commit_id\":\"',
                            e1.ei_commit_id,
                            '\",\"commit_message\":\"',
                            REPLACE(e1.commit_message, '\"', '\''),
                            '\",\"commit_user_id\":\"',
                            e1.commit_user_id,
                            '\",\"commit_datetime\":\"',
                            e1.commit_datetime,
                            '\",\"ei_git_repo_subject_branch_id\":\"',
                            e1.ei_git_repo_subject_branch_id,
                            '\",\"ei_git_repo_id\":\"',
                            e1.ei_git_repo_id,
                            '\",\"commit_ei_subject_id\":\"',
                            e1.commit_ei_subject_id,
                            '\"}')),
                ']') AS commit_json
            FROM
                ei_git_repo_subject_commit e1
            WHERE
                e1.ei_git_repo_id = :ei_git_repo_id
                    AND e1.ei_git_repo_subject_branch_id = 0 and e1.commit_ei_subject_id=:commit_ei_subject_id"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id, 
                'commit_ei_subject_id' => $d->commit_ei_subject_id
            ]
        );

        $commit_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($commit_list);
        
        return true;
    }

    /**
     * Recuperer liste des branches en fonction du repo par rapport a la delivery
     * 
     * @return array
     */
    function getGitDeliveryRepoBranch()
    {
        $d = $this->checkParams(
            [
                'ei_delivery_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
            ei_git_repo_delivery_branch egrsb
                INNER JOIN
            ei_git_repo egr ON egr.ei_git_repo_id = egrsb.ei_git_repo_id
                INNER JOIN
            ei_git_repo_trunk egrb ON egrb.ei_git_repo_trunk_id = egrsb.ei_git_repo_trunk_parent_id
                AND egrb.ei_git_repo_id = egrsb.ei_git_repo_id
            WHERE
                egrsb.ei_delivery_id =:ei_delivery_id"
        );
        $s->execute(
            [
                'ei_delivery_id' => $d->ei_delivery_id
            ]
        );

        $branch_list = $s->fetchAll(PDO::FETCH_ASSOC);

        foreach ($branch_list as $key => $value) {
            $s = $this->PDO->prepare(
                "SELECT 
                    CONCAT('[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'title', es.title,
                                'ei_subject_id', es.ei_subject_id,
                                'ei_subject_user_in_charge', es.ei_subject_user_in_charge,
                                'creator_id', es.creator_id,
                                'creator_picture_path', eu.picture_path,
                                'in_charge_picture_path', eu1.picture_path,
                                'ei_pool_id', es.ei_pool_id,
                                'ei_pool_color', ep.pool_color,
                                'ei_pool_name', ep.pool_name,
                                'ref_subject_type_id', es.ref_subject_type_id,
                                'type_icon', rst.type_icon,
                                'type_name', rst.type_name,
                                'ref_subject_status_id', es.ref_subject_status_id,
                                'status_name', rss.status_name,
                                'status_icon', rss.status_icon,
                                'ref_subject_priority_id', es.ref_subject_priority_id,
                                'priority_name', rsp.priority_name,
                                'priority_picto', rsp.priority_picto,
                                'delivery_name', ed.delivery_name,
                                'ei_delivery_id', ed.ei_delivery_id,
                                'subject_branch_name', egrsb.subject_branch_name
                            )
                        SEPARATOR ','),
                    ']') AS json_result
                FROM
                    ei_subject es
                        INNER JOIN
                    ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id
                        INNER JOIN
                    ei_git_repo_delivery_branch egrdb ON egrdb.ei_delivery_id = ed.ei_delivery_id
                        INNER JOIN
                    ei_git_repo_subject_branch egrsb ON egrsb.ei_git_repo_id = egrdb.ei_git_repo_id
                    AND egrsb.ei_subject_id = es.ei_subject_id
                        inner join
                    ei_pool ep on ep.ei_pool_id = es.ei_pool_id
                        inner join 
                    ei_user eu on eu.ei_user_id=es.creator_id
                        inner join 
                    ei_user eu1 on eu1.ei_user_id=es.ei_subject_user_in_charge
                        inner join 
                    ref_subject_type rst on rst.ref_subject_type_id=es.ref_subject_type_id
                        inner join 
                    ref_subject_priority rsp on rsp.ref_subject_priority_id=es.ref_subject_priority_id
                        inner join 
                    ref_subject_status rss on rss.ref_subject_status_id=es.ref_subject_status_id
                WHERE
                    ed.ei_delivery_id = :ei_delivery_id
                    AND es.ei_subject_version_id = (
                        SELECT MAX(s2.ei_subject_version_id)
                        FROM ei_subject s2
                        WHERE s2.ei_subject_id = es.ei_subject_id
                    )
                    AND egrdb.ei_git_repo_id = :ei_git_repo_id"
            );
            $s->execute(
                [
                    'ei_delivery_id' => $d->ei_delivery_id,
                    'ei_git_repo_id' => $value['ei_git_repo_id']
                ]
            );

            $subjectConnected = $s->fetch(PDO::FETCH_ASSOC);
            $branch_list[$key]['subjectConnected'] = $subjectConnected['json_result'];
        }

        $this->setData($branch_list);
        
        return true;
    }

    /**
     * Recuperer liste des branches en fonction du repo par rapport a la delivery
     * 
     * @return array
     */
    function getGitDeliveryRepoBranchTrunkMergeInfo()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_delivery_branch_id' => 'int',
                'trunk_type'=>'string'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                max(commit_merge_datetime) as max_merge
            FROM
                ei_git_repo_delivery_commit
            WHERE
                ei_git_repo_delivery_branch_id = :ei_git_repo_delivery_branch_id
                    AND commit_merge_branch_trunk = :trunk_type"
        );
        $s->execute(
            [
                'ei_git_repo_delivery_branch_id' => $d->ei_git_repo_delivery_branch_id,
                'trunk_type' => $d->trunk_type
            ]
        );

        $MaxMergeTrunk = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($MaxMergeTrunk);
        
        return true;
    }
    

    /**
     * Recuperer la branch integration en fonction du repo par rapport au subject
     * 
     * @return array
     */
    function getGitIntegrationBranchWithRepoAndSubject()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_delivery_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_trunk egrb
                    INNER JOIN
                ei_git_repo_delivery_branch egrdb ON egrdb.ei_git_repo_trunk_parent_id = egrb.ei_git_repo_trunk_id
            WHERE
                egrb.ei_git_repo_id = :ei_git_repo_id
                    AND egrdb.ei_delivery_id = :ei_delivery_id"
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_delivery_id' => $d->ei_delivery_id
            ]
        );

        $branch_list = $s->fetch(PDO::FETCH_ASSOC);

        $this->setData($branch_list);
        
        return true;
    }

    /**
     * recuperer tout le commit history en bdd
     * 
     * @return array
     */
    function getAllCommitHistory()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int'
            ]
        );

        $d = $this->initOptionalParams('filter', 'string', 'none');

        if($d->filter === 'connected') {
            $s = $this->PDO->prepare(
                "SELECT * FROM ei_git_repo_subject_commit egrsc
        LEFT JOIN
    ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
        AND es.ei_subject_version_id = (SELECT 
            MAX(es2.ei_subject_version_id)
        FROM
            ei_subject es2
        WHERE
            es2.ei_subject_id = egrsc.commit_ei_subject_id)
        LEFT JOIN
    ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id where ei_git_repo_id=:ei_git_repo_id AND commit_ei_subject_id != 0 order by commit_datetime desc"
            );
        } else if ($d->filter === 'not_connected') {
            $s = $this->PDO->prepare(
                "SELECT * FROM ei_git_repo_subject_commit egrsc
        LEFT JOIN
    ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
        AND es.ei_subject_version_id = (SELECT 
            MAX(es2.ei_subject_version_id)
        FROM
            ei_subject es2
        WHERE
            es2.ei_subject_id = egrsc.commit_ei_subject_id)
        LEFT JOIN
    ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id where ei_git_repo_id=:ei_git_repo_id AND commit_ei_subject_id = 0 order by commit_datetime desc"
            );
        } else if ($d->filter === 'commit_ignored') {
            $s = $this->PDO->prepare(
                "SELECT * FROM ei_git_repo_subject_commit egrsc
        LEFT JOIN
    ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
        AND es.ei_subject_version_id = (SELECT 
            MAX(es2.ei_subject_version_id)
        FROM
            ei_subject es2
        WHERE
            es2.ei_subject_id = egrsc.commit_ei_subject_id)
        LEFT JOIN
    ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id where ei_git_repo_id=:ei_git_repo_id AND commit_ignored = 'Y' order by commit_datetime desc"
            );
        } else if ($d->filter === 'commit_brick') {
            $s = $this->PDO->prepare(
                "SELECT 
                *
            FROM
                ei_git_repo_subject_commit egrsc
            LEFT JOIN
            ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
                AND es.ei_subject_version_id = (SELECT 
                    MAX(es2.ei_subject_version_id)
                FROM
                    ei_subject es2
                WHERE
                    es2.ei_subject_id = egrsc.commit_ei_subject_id)
                LEFT JOIN
            ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id
            WHERE
                ei_git_repo_id = :ei_git_repo_id
                    AND commit_ei_subject_id != 0 order by commit_datetime desc"
            );
        } else {
            $s = $this->PDO->prepare(
                "SELECT * FROM ei_git_repo_subject_commit egrsc
        LEFT JOIN
    ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
        AND es.ei_subject_version_id = (SELECT 
            MAX(es2.ei_subject_version_id)
        FROM
            ei_subject es2
        WHERE
            es2.ei_subject_id = egrsc.commit_ei_subject_id)
        LEFT JOIN
    ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id where ei_git_repo_id=:ei_git_repo_id order by commit_datetime desc;"
            );
        }

        
        $s->execute(['ei_git_repo_id' => $d->ei_git_repo_id]);

        $repo_commit_list = $s->fetchAll(PDO::FETCH_ASSOC);
        $this->setData($repo_commit_list);
        
        return true;
    }

    /**
     * recuperer tout le commit history en bdd qui sont assigner a un subject
     * 
     * @return array
     */
    function getAllCommitHistoryConnectedToSubject()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_repo_subject_commit egrsc
            LEFT JOIN
            ei_subject es ON es.ei_subject_id = egrsc.commit_ei_subject_id
                AND es.ei_subject_version_id = (SELECT 
                    MAX(es2.ei_subject_version_id)
                FROM
                    ei_subject es2
                WHERE
                    es2.ei_subject_id = egrsc.commit_ei_subject_id)
                LEFT JOIN
            ei_delivery ed ON ed.ei_delivery_id = es.ei_delivery_id
            WHERE
                ei_git_repo_id = :ei_git_repo_id
                    AND commit_ei_subject_id != 0"
        );
        $s->execute(['ei_git_repo_id' => $d->ei_git_repo_id]);

        $repo_commit_list = $s->fetchAll(PDO::FETCH_ASSOC);
        $this->setData($repo_commit_list);
        
        return true;
    }

    /**
     * Ajouter tout le commit history en bdd
     * 
     * @return array
     */
    function addAllCommitHistory()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'array_commit_list' => 'html'
            ]
        );

        foreach ($d->array_commit_list as $git_commit) {
            // error_log($git_commit->message);

            $s = $this->PDO->prepare(
                "INSERT IGNORE INTO `ei_git_repo_subject_commit` 
                (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `commit_parent_id`, `commit_datetime`, `commit_message`, `commit_user_id`,`commit_ei_subject_id`) 
                VALUES (:repo_id, '', :ei_commit_id, :ei_commit_parent_id, :commit_date,:ei_commit_message,:ei_commit_user_id,'')"
            );
            $s->execute(
                [
                    'repo_id' => $d->ei_git_repo_id,
                    'ei_commit_id' => $git_commit->commit_name,
                    'ei_commit_parent_id' => $git_commit->parent_id,
                    'ei_commit_message'=>$git_commit->message,
                    'commit_date'=>$git_commit->date,
                    'ei_commit_user_id'=>$this->user['ei_user_id'],
                ]
            );

        }
        
        return true;
    }

    /**
     * Ajouter le commit du subject
     * 
     * @return array
     */
    function addGitRepoCommit()
    {
        $d = $this->checkParams(
            [
                'repo_id' => 'int',
                'ei_commit_id' => 'string',
                'ei_commit_parent_id' => 'html',
                'ei_commit_message' => 'string',
            ]
        );

        $d = $this->initOptionalParams('ei_git_repo_subject_branch_id', 'int', 0);
        $d = $this->initOptionalParams('ei_git_repo_delivery_branch_id', 'int', 0);
        $d = $this->initOptionalParams('ei_subject_id', 'int', 0);
        $d = $this->initOptionalParams('ei_delivery_id', 'int', 0);
        $d = $this->initOptionalParams('delivery_commit', 'string', 'false');
        $d = $this->initOptionalParams('subject_commit', 'string', 'false');
        $d = $this->initOptionalParams('commit_merge_datetime', 'string', '');
        $d = $this->initOptionalParams('commit_merge_branch_trunk', 'string', '');

        $d = $this->initOptionalParams('git_file_array', 'html', '');
        if ($d->subject_commit === 'true') {
            /* Inserting a new row into the ei_git_repo_subject_commit table. */
            // $s = $this->PDO->prepare(
            //     "SELECT max(ei_git_commit_id+1)FROM ei_git_repo_subject_commit;"
            // );
            // $s->execute(
            //     [
            //     ]
            // );
            // $max_commit_id = (int)($s->fetch()?:[0])[0];
            // if ($max_commit_id == 0) {
            //     $max_commit_id = 1;
            // }
            error_log(strlen($d->commit_merge_datetime));
            if (strlen($d->commit_merge_datetime) >=2) {
                error_log('if');
                $s = $this->PDO->prepare(
                    "INSERT IGNORE INTO `ei_git_repo_subject_commit` 
                    (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `commit_parent_id`, `commit_datetime`, `commit_message`, `commit_user_id`,`commit_ei_subject_id`,`commit_merge_datetime`) 
                    VALUES (:repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_commit_parent_id, now(),:ei_commit_message,:ei_commit_user_id,:ei_subject_id,NOW())"
                );
                $s->execute(
                    [
                        'repo_id' => $d->repo_id,
                        'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                        'ei_commit_id' => $d->ei_commit_id,
                        'ei_commit_parent_id' => $d->ei_commit_parent_id,
                        'ei_commit_message'=>$d->ei_commit_message,
                        'ei_commit_user_id'=>$this->user['ei_user_id'],
                        'ei_subject_id'=> $d->ei_subject_id
                    ]
                );
            } else {
                error_log('else');
                $s = $this->PDO->prepare(
                "INSERT IGNORE INTO `ei_git_repo_subject_commit` 
                (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `commit_parent_id`, `commit_datetime`, `commit_message`, `commit_user_id`,`commit_ei_subject_id`) 
                VALUES (:repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_commit_parent_id, now(),:ei_commit_message,:ei_commit_user_id,:ei_subject_id)"
            );
            $s->execute(
                [
                    'repo_id' => $d->repo_id,
                    'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                    'ei_commit_id' => $d->ei_commit_id,
                    'ei_commit_parent_id' => $d->ei_commit_parent_id,
                    'ei_commit_message'=>$d->ei_commit_message,
                    'ei_commit_user_id'=>$this->user['ei_user_id'],
                    'ei_subject_id'=> $d->ei_subject_id
                ]
            );
            }
            

            if ($d->git_file_array != '') {
                $git_file_array = json_decode($d->git_file_array);
                foreach ($git_file_array as $git_file) {



                    // Faire un call sql qui va verifier si le fichier existe deja dans la table ou non
                    // s'il existe deja on get l'id sinon on le crée

                    $s = $this->PDO->prepare(
                        "SELECT count(1) FROM ei_git_file where ei_git_file_path=:ei_git_file_path and ei_git_file_name=:ei_git_file_name and ei_git_repo_id=:ei_git_repo_id"
                    );
                    $s->execute(
                        [
                            'ei_git_file_name' => $git_file->file_name,
                            'ei_git_file_path' => $git_file->file_path,
                            'ei_git_repo_id' => $d->repo_id
                        ]
                    );

                    $countFileWithRepo = (int)($s->fetch()?:[0])[0];
                    if ($countFileWithRepo == 0) {
                        /* Inserting the file information into the database. */
                        $s = $this->PDO->prepare(
                            "SELECT max(ei_git_file_id)+1 FROM ei_git_file;"
                        );
                        $s->execute([]);

                        $max_file_id = (int)($s->fetch()?:[0])[0];
                        if ($max_file_id == 0) {
                            $max_file_id = 1;
                        }


                        $s = $this->PDO->prepare(
                            "INSERT IGNORE INTO `ei_git_file` (`ei_git_repo_id`, `ei_git_file_id`, `ei_git_file_path`, `ei_git_file_name`) VALUES (:ei_git_repo_id, :ei_git_file_id, :ei_git_file_path, :ei_git_file_name);"
                        );
                        $s->execute(
                            [
                                'ei_git_file_id' => $max_file_id,
                                'ei_git_file_name' => $git_file->file_name,
                                'ei_git_file_path' => $git_file->file_path,
                                'ei_git_repo_id' => $d->repo_id
                            ]
                        );
                        // error_log(json_encode(explode(' ', $git_file->file_path)));
                        $fil_path_array = explode('/', $git_file->file_path);
                        // error_log(json_encode($fil_path_array));

                        $s = $this->PDO->prepare(
                            "SELECT ei_git_repo_name FROM ei_git_repo where ei_git_repo_id=:ei_git_repo_id;"
                        );
                        $s->execute(['ei_git_repo_id' => $d->repo_id]);

                        $repo_name = $s->fetch(PDO::FETCH_ASSOC);


                        $numItems = count($fil_path_array);
                        $i = 0;
                        foreach ($fil_path_array as $key => $value) {
                            // error_log($value);
                            // error_log($key);
                            ++$i;
                            // error_log('pas le dernier');
                            $s = $this->PDO->prepare(
                                "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:foldername and ei_git_id=:ei_git_id;"
                            );
                            $s->execute(
                                [
                                    'foldername' => $value,
                                    'ei_git_id' => $d->repo_id
                                ]
                            );
                            $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                            if ($ei_brick_tree_node_id) {
                                // error_log('existe deja');
                            } else {
                                // error_log('n existe pas');
                                    // recuperation du ma node_id pour la brick 
                                $s = $this->PDO->prepare(
                                    "SELECT max(ei_brick_tree_node_id)+1 as max_node_id from ei_git_file_brick_tree"
                                );
                                $s->execute([]);

                                $max_node_brick_id_tree = (int)($s->fetch()?:[0])[0];
                                if ($max_node_brick_id_tree == 0) {
                                    $max_node_brick_id_tree = 1;
                                }

                                
                                // error_log('i value : ' . $i);
                                if ($i === 1) {
                                    $s = $this->PDO->prepare(
                                        "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:repo_name and ei_git_id=:ei_git_id;"
                                    );
                                    $s->execute(
                                        [
                                            'repo_name' => $repo_name['ei_git_repo_name'],
                                            'ei_git_id' => $d->repo_id
                                        ]
                                    );
                                    $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                                } else {
                                    $s = $this->PDO->prepare(
                                        "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:foldername and ei_git_id=:ei_git_id;"
                                    );
                                    $s->execute(
                                        [
                                            'foldername' => $fil_path_array[$key - 1],
                                            'ei_git_id' => $d->repo_id
                                        ]
                                    );
                                    $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                                }

                                // recuperation de max pos id 

                                $s = $this->PDO->prepare(
                                    "SELECT max(position)+1 as max_pos_node_id from ei_git_file_brick_tree where ei_brick_tree_parent_node_id=:ei_brick_tree_parent_node_id"
                                );
                                $s->execute(
                                    [
                                        'ei_brick_tree_parent_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                    ]
                                );

                                $max_pos_node_id_tree = (int)($s->fetch()?:[0])[0];
                                if ($max_pos_node_id_tree == 0) {
                                    $max_pos_node_id_tree = 1;
                                }

                                $s = $this->PDO->prepare(
                                    "INSERT INTO `ei_git_file_brick_tree` (`ref_object_familly_id`, `ei_brick_tree_parent_node_id`, `ei_brick_tree_node_id`, `position`, foldername,`ei_git_brick_id`, `showed`,ei_git_id) 
                                    VALUES ('BRK', :ei_brick_tree_node_id, :max_node_brick_id, :max_pos_node_id,:foldername ,null, 'Y',:ei_git_id);"
                                );
                                $s->execute(
                                    [
                                        'foldername' => $value,
                                        'max_node_brick_id' => $max_node_brick_id_tree,
                                        'max_pos_node_id' => $max_pos_node_id_tree,
                                        'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id'],
                                        'ei_git_id' => $d->repo_id
                                    ]
                                );

                            }
                        }
                    } else {
                        $s = $this->PDO->prepare(
                            "SELECT ei_git_file_id FROM ei_git_file where ei_git_file_path=:ei_git_file_path and ei_git_file_name=:ei_git_file_name and ei_git_repo_id=:ei_git_repo_id"
                        );
                        $s->execute(
                            [
                                'ei_git_file_name' => $git_file->file_name,
                                'ei_git_file_path' => $git_file->file_path,
                                'ei_git_repo_id' => $d->repo_id
                            ]
                        );
                        $max_file_id = (int)($s->fetch()?:[0])[0];
                    }
                    

                    $s = $this->PDO->prepare(
                        "INSERT INTO `ei_git_repo_subject_commit_git_file` (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `ei_git_file_id`, `action`) 
                        VALUES (:ei_git_repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_git_file_id, :action);"
                    );
                    $s->execute(
                        [
                            'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                            'ei_commit_id' =>  $d->ei_commit_id,
                            'ei_git_file_id' => $max_file_id,
                            'ei_git_repo_id' => $d->repo_id,
                            'action' => $git_file->status
                        ]
                    );

                    if (array_key_exists('file_changes', $git_file)) {
                        $git_file_array_brick = $git_file->file_changes;
                        // error_log('file_changes : ' . $git_file->file_name);
                        foreach ($git_file_array_brick as $git_brick) {


                            // Verifier si la brick existe deja dans la table ou non

                            $s = $this->PDO->prepare(
                                "SELECT count(1) FROM ei_git_file_brick where ei_git_brick_type=:ei_git_brick_type and ei_git_brick_name=:ei_git_brick_name and ei_git_repo_id=:ei_git_repo_id and ei_git_file_id=:ei_git_file_id"
                            );
                            $s->execute(
                                [
                                    'ei_git_brick_type' => $git_brick->type,
                                    'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                    'ei_git_repo_id' => $d->repo_id,
                                    'ei_git_file_id' => $max_file_id,
                                ]
                            );

                            $countFileBrick = (int)($s->fetch()?:[0])[0];

                            if ($countFileBrick == 0) {
                                /* Inserting the new brick into the database. */
                                $s = $this->PDO->prepare(
                                    "SELECT max(ei_git_brick_id)+1 FROM ei_git_file_brick;"
                                );
                                $s->execute([]);

                                $max_brick_id = (int)($s->fetch()?:[0])[0];
                                if ($max_brick_id == 0) {
                                    $max_brick_id = 1;
                                }

                                switch ($git_brick->type) {
                                case 'class':
                                    $name_class = str_replace('"', '', $git_brick->name);
                                    $name_function = '';
                                    $name_method = '';
                                    break;
                                case 'function':
                                    $name_class = '';
                                    $name_function = str_replace('"', '', $git_brick->name);
                                    $name_method = '';
                                    break;
                                case 'method':
                                    $name_class = '';
                                    $name_function = '';
                                    $name_method = str_replace('"', '', $git_brick->name);
                                    break;

                                }

                                $s = $this->PDO->prepare(
                                    "INSERT INTO `ei_git_file_brick` 
                                    (`ei_git_repo_id`, `ei_git_file_id`, `ei_git_brick_id`, `ei_git_brick_name`, `ei_git_brick_type`, `class_name`, `class_methode_name`, `function_name`) 
                                    VALUES (:ei_git_repo_id, :ei_git_file_id, :ei_git_brick_id, :ei_git_brick_name, :ei_git_brick_type, :name_class, :name_method, :name_function);"
                                );
                                $s->execute(
                                    [
                                        'ei_git_brick_id' => $max_brick_id,
                                        'ei_git_brick_type' => $git_brick->type,
                                        'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                        'ei_git_repo_id' => $d->repo_id,
                                        'ei_git_file_id' => $max_file_id,
                                        'name_class' => $name_class,
                                        'name_function' => $name_function,
                                        'name_method' => $name_method
                                    ]
                                );

                                // $s = $this->PDO->prepare(
                                //     "SELECT max(ei_git_subject_brick_link_id)+1 FROM ei_git_subject_brick_link;"
                                // );
                                // $s->execute([]);

                                // $max_subject_brick_link_id = (int)($s->fetch()?:[0])[0];
                                // if($max_subject_brick_link_id === 0){
                                //     $max_subject_brick_link_id = 1;
                                // }

                                // $s = $this->PDO->prepare(
                                //     "INSERT INTO `ei_git_subject_brick_link` (`ei_git_subject_brick_link_id`, `ei_git_brick_id`, `ei_subject_id`) VALUES (:max_subject_brick_link_id, :ei_git_brick_id, :ei_subject_id);"
                                // );
                                // $s->execute(
                                //     [
                                //         'ei_git_brick_id' => $max_brick_id,
                                //         'ei_subject_id' => $d->ei_subject_id,
                                //         'max_subject_brick_link_id' => $max_subject_brick_link_id
                                //     ]
                                // );

                                $this->callClass(
                                    "Git", 
                                    "addSubjectBrickLink", 
                                    [
                                        'ei_git_brick_id' => $max_brick_id,
                                        'ei_subject_id' => $d->ei_subject_id
                                    ]
                                );


                                // recuperation du ma node_id pour la brick 

                                $s = $this->PDO->prepare(
                                    "SELECT max(ei_brick_tree_node_id)+1 as max_node_id from ei_git_file_brick_tree"
                                );
                                $s->execute([]);

                                $max_node_brick_id = (int)($s->fetch()?:[0])[0];
                                if ($max_node_brick_id == 0) {
                                    $max_node_brick_id = 1;
                                }

                               

                                // error_log("max_brick_id : " . $max_brick_id);
                                // error_log("max_node_brick_id : " . $max_node_brick_id);
                                
                                $s = $this->PDO->prepare(
                                    "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:repo_name and ei_git_id=:ei_git_id;"
                                );
                                $s->execute(
                                    [
                                        'repo_name' => $git_file->file_name,
                                        'ei_git_id' => $d->repo_id
                                    ]
                                );
                                $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                                // error_log("ei_brick_tree_node_id : " . $ei_brick_tree_node_id['ei_brick_tree_node_id']);


                                 // recuperation de max pos id 

                                $s = $this->PDO->prepare(
                                    "SELECT max(position)+1 as max_pos_node_id from ei_git_file_brick_tree where ei_brick_tree_parent_node_id=:ei_brick_tree_node_id"
                                );
                                $s->execute(
                                    [
                                        'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                    ]
                                );

                                $max_pos_node_id = (int)($s->fetch()?:[0])[0];
                                if ($max_pos_node_id == 0) {
                                    $max_pos_node_id = 1;
                                }

                                
                                switch ($git_brick->type) {
                                case 'class':
                                        //recuperer le ei_brick_tree_node_id si le dossier existe deja 
                                        $s = $this->PDO->prepare(
                                            "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where  ei_brick_tree_node_id=:ei_brick_tree_node_id"
                                        );

                                        $s->execute(
                                            [
                                                'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                            ]
                                        );

                                        $tree_node_id = (int)($s->fetch()?:[0])[0];
                                        $tree_node_familly_type = 'BRKC';


                                    
                                    break;
                                case 'function':

                                   
                                        //recuperer le ei_brick_tree_node_id si le dossier existe deja 
                                        $s = $this->PDO->prepare(
                                            "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where  ei_brick_tree_node_id=:ei_brick_tree_node_id"
                                        );

                                        $s->execute(
                                            [
                                                'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                            ]
                                        );

                                        $tree_node_id = (int)($s->fetch()?:[0])[0];

                                    $tree_node_familly_type = 'BRKF';
                                    break;
                                case 'method':

                                    // verifier si le dossier class existe deja dans le parent
                                    $s = $this->PDO->prepare(
                                        "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where ref_object_familly_id='BRKC' and ei_brick_tree_parent_node_id=:ei_brick_tree_node_id"
                                    );
                                    $s->execute(
                                        [
                                            'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                        ]
                                    );

                                   
                                    $tree_node_id = (int)($s->fetch()?:[0])[0];
                                    $tree_node_familly_type = 'BRKM';
                                    break;

                                }




                                // ajout des nodes dans l'arbre des brick
                                $s = $this->PDO->prepare(
                                    "INSERT INTO `ei_git_file_brick_tree` (`ref_object_familly_id`, `ei_brick_tree_parent_node_id`, `ei_brick_tree_node_id`, `position`, `ei_git_brick_id`, `showed`,ei_git_id) 
                                    VALUES (:tree_node_familly_type, :ei_brick_tree_node_id, :max_node_brick_id, :max_pos_node_id, :ei_git_brick_id, 'Y',:ei_git_id);"
                                );
                                $s->execute(
                                    [
                                        'ei_git_brick_id' => $max_brick_id,
                                        'ei_brick_tree_node_id' => $tree_node_id,
                                        'max_node_brick_id' => $max_node_brick_id,
                                        'max_pos_node_id' => $max_pos_node_id,
                                        'tree_node_familly_type' => $tree_node_familly_type,
                                        'ei_git_id' => $d->repo_id
                                    ]
                                );



                            } else {
                                $s = $this->PDO->prepare(
                                    "SELECT ei_git_brick_id FROM ei_git_file_brick where ei_git_brick_type=:ei_git_brick_type and ei_git_brick_name=:ei_git_brick_name and ei_git_repo_id=:ei_git_repo_id and ei_git_file_id=:ei_git_file_id"
                                );
                                $s->execute(
                                    [
                                        'ei_git_brick_type' => $git_brick->type,
                                        'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                        'ei_git_repo_id' => $d->repo_id,
                                        'ei_git_file_id' => $max_file_id,
                                    ]
                                );
                                $max_brick_id = (int)($s->fetch()?:[0])[0];
                            }

                            $s = $this->PDO->prepare(
                                "INSERT INTO `ei_git_repo_subject_commit_git_brick` 
                                (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `ei_git_file_id`, `ei_git_brick_id`,brick_code,brick_datetime, `action`) 
                                VALUES (:ei_git_repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_git_file_id, :ei_git_brick_id,:brick_code,now(),:action);"
                            );
                            $s->execute(
                                [
                                    'ei_commit_id' =>  $d->ei_commit_id,
                                    'ei_git_brick_id' => $max_brick_id,
                                    'action' => $git_brick->status,
                                    'ei_git_repo_id' => $d->repo_id,
                                    'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                                    'brick_code' => $git_brick->code,
                                    'ei_git_file_id' => $max_file_id,
                                ]
                            );
                        }
                    }
                    
                }
            }
        } else if ($d->delivery_commit === 'true') {
            $s = $this->PDO->prepare(
                "SELECT max(ei_git_commit_id+1)FROM ei_git_repo_delivery_commit;"
            );
            $s->execute( [ ] );
            $max_commit_id = (int)($s->fetch()?:[0])[0];
            if ($max_commit_id == 0) {
                $max_commit_id = 1;
            }
            $s = $this->PDO->prepare(
                "INSERT INTO `ei_git_repo_delivery_commit` 
                (`ei_git_repo_id`, `ei_git_repo_delivery_branch_id`, `ei_git_commit_id`,
                `ei_commit_id`, `ei_commit_parent_id`, `ei_commit_datetime`, `ei_commit_message`, `ei_commit_user_id`,`commit_merge_datetime`,`commit_merge_branch_trunk`) 
                VALUES (:repo_id, :ei_git_repo_delivery_branch_id, :max_commit_id, :ei_commit_id, :ei_commit_parent_id, now(),:ei_commit_message,:ei_commit_user_id, now(),:commit_merge_branch_trunk )"
            );
            $s->execute(
                [
                    'max_commit_id' => $max_commit_id,
                    'repo_id' => $d->repo_id,
                    'ei_git_repo_delivery_branch_id' => $d->ei_git_repo_delivery_branch_id,
                    'ei_commit_id' => $d->ei_commit_id,
                    'ei_commit_parent_id' => $d->ei_commit_parent_id,
                    'ei_commit_message'=>$d->ei_commit_message,
                    'ei_commit_user_id'=>$this->user['ei_user_id'],
                    'commit_merge_branch_trunk'=>$d->commit_merge_branch_trunk
                ]
            );
        }
        
        
        return true;
    }

    /**
     * Recuperer la liste des user qui sont pin sur le subject
     * 
     * @return array
     */
    function getGitUserList()
    {
        $d = $this->checkParams(
            [
                'ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                ei_user_id, username, picture_path
            FROM
                ei_user
            WHERE
                current_subject_id = :ei_subject_id"
        );
        $s->execute(
            [
                'ei_subject_id' => $d->ei_subject_id
            ]
        );

        $user_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($user_list);
        
        return true;
    }


    /**
     * Recuperer toutes les infos en fonction du brick_id
     * 
     * @return array
     */
    function getGitFileBrick()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_file_brick
            WHERE
                ei_git_brick_id = :ei_git_brick_id"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );

        $brick_infos = $s->fetch(PDO::FETCH_ASSOC);


        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_git_file_brick egfb
                    INNER JOIN
                ei_git_repo_subject_commit_git_brick egrscgb ON egrscgb.ei_git_brick_id = egfb.ei_git_brick_id
                    inner join ei_git_repo_subject_commit egrsc on egrsc.ei_commit_id=egrscgb.ei_commit_id
                    inner join ei_user eu on eu.ei_user_id=egrsc.commit_user_id
                    -- inner join ei_git_repo_subject_branch esb on esb.ei_git_repo_subject_branch_id=egrsc.ei_git_repo_subject_branch_id
                    left outer join ei_git_repo_subject_branch esb on esb.ei_git_repo_subject_branch_id=(egrsc.ei_git_repo_subject_branch_id or 0)
            WHERE
                egfb.ei_git_brick_id =:ei_git_brick_id order by egrscgb.brick_datetime desc"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );

        $brick_commit_list = $s->fetchAll(PDO::FETCH_ASSOC);

        $functionlist = $this->callClass(
            "Git", 
            "getBrickFunctionList", 
            [
                'ei_git_brick_id' => $d->ei_git_brick_id

            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                egsbl.ei_git_brick_id,
                u3.subject_user_pin,
                esr.risk_exec,
                esr.risk_exec_ok,
                esr.risk_exec_ko,
                esr.total_risk,
                edsre.risk_delivery_exec_ok,
                edsre.risk_delivery_exec_ko,
                s.ei_subject_id,
                s.ei_subject_external_id,
                s.title,
                p.ei_pool_id,
                p.pool_color,
                p.pool_name,
                d.ei_delivery_id,
                d.delivery_date,
                d.delivery_name,
                rds.is_final,
                st.ref_subject_type_id,
                st.type_name,
                st.type_icon,
                ss.ref_subject_status_id,
                ss.status_name,
                ss.color AS status_color,
                ss.status_icon,
                sp.ref_subject_priority_id,
                sp.priority_name,
                sp.color AS priority_color,
                sp.priority_picto,
                u.username,
                u.picture_path,
                u2.username AS in_charge_username,
                u2.picture_path AS in_charge_picture_path,
                s.created_at,
                DATEDIFF(NOW(), s.created_at) AS diff_days,
                et.iteration_description,
                COALESCE(it_list.list_iteration,
                        'pas&nbsp;de&nbsp;liste') AS list_iteration
            FROM
                ei_subject s
                    LEFT OUTER JOIN
                ei_git_subject_brick_link egsbl ON egsbl.ei_subject_id = s.ei_subject_id
                    LEFT OUTER JOIN
                ei_git_file_brick egfb ON egfb.ei_git_brick_id = egsbl.ei_git_brick_id
                    LEFT OUTER JOIN
                ei_pool p ON s.ei_pool_id = p.ei_pool_id
                    LEFT OUTER JOIN
                ei_delivery d ON s.ei_delivery_id = d.ei_delivery_id
                    LEFT OUTER JOIN
                ref_delivery_status rds ON d.ref_delivery_type_status_id = rds.ref_delivery_type_status_id
                    LEFT OUTER JOIN
                ref_subject_type st ON s.ref_subject_type_id = st.ref_subject_type_id
                    LEFT OUTER JOIN
                ref_subject_status ss ON s.ref_subject_status_id = ss.ref_subject_status_id
                    LEFT OUTER JOIN
                ref_subject_priority sp ON s.ref_subject_priority_id = sp.ref_subject_priority_id
                    LEFT OUTER JOIN
                ei_user u ON s.creator_id = u.ei_user_id
                    LEFT OUTER JOIN
                ei_user u2 ON s.ei_subject_user_in_charge = u2.ei_user_id
                    LEFT OUTER JOIN
                (SELECT 
                    current_subject_id,
                        CONCAT('[', GROUP_CONCAT('{\"username\":\"', username, '\",\"picture_path\":\"', picture_path, '\"}'
                            SEPARATOR ', '), ']') AS subject_user_pin
                FROM
                    ei_user
                GROUP BY current_subject_id) u3 ON u3.current_subject_id = s.ei_subject_id
                    LEFT OUTER JOIN
                (SELECT 
                    esr.ei_subject_id,
                        SUM((SELECT 
                                COUNT(1)
                            FROM
                                ei_function_stat efs
                            WHERE
                                efs.nb_ok > 1
                                    AND efs.ei_function_id = esr.ei_function_id
                                    AND ei_iteration_id = 17)) AS risk_exec,
                        COUNT(*) AS total_risk,
                        SUM((SELECT 
                                COUNT(1)
                            FROM
                                ei_function_stat efs
                            WHERE
                                efs.last_status = 'ok'
                                    AND efs.ei_function_id = esr.ei_function_id
                                    AND ei_iteration_id = 17)) AS risk_exec_ok,
                        SUM((SELECT 
                                COUNT(1)
                            FROM
                                ei_function_stat efs
                            WHERE
                                efs.last_status = 'ko'
                                    AND efs.ei_function_id = esr.ei_function_id
                                    AND ei_iteration_id = 17)) AS risk_exec_ko
                FROM
                    ei_subject_risk esr
                LEFT OUTER JOIN ei_function_stat eess ON eess.ei_function_id = esr.ei_function_id
                WHERE
                    esr.ei_subject_id = esr.ei_subject_id
                GROUP BY esr.ei_subject_id) esr ON esr.ei_subject_id = s.ei_subject_id
                    LEFT OUTER JOIN
                ei_delivery_subject_risk_exec edsre ON edsre.ei_subject_id = s.ei_subject_id
                    LEFT OUTER JOIN
                ei_iteration et ON et.ei_iteration_id = 17
                    LEFT OUTER JOIN
                (SELECT 
                    GROUP_CONCAT(et.iteration_description
                            SEPARATOR ', ') AS list_iteration,
                        edi.ei_delivery_id
                FROM
                    ei_delivery_iteration edi
                LEFT OUTER JOIN ei_iteration et ON et.ei_iteration_id = edi.ei_iteration_id
                GROUP BY edi.ei_delivery_id) it_list ON it_list.ei_delivery_id = d.ei_delivery_id
            WHERE
                s.ei_subject_version_id = (SELECT 
                        MAX(s2.ei_subject_version_id)
                    FROM
                        ei_subject s2
                    WHERE
                        s2.ei_subject_id = s.ei_subject_id)
                    AND egsbl.ei_git_brick_id = :ei_git_brick_id
            ORDER BY s.ei_subject_id DESC;"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );

        $brick_subject_list = $s->fetchAll(PDO::FETCH_ASSOC);
        

        $brick_data['brick_infos'] = $brick_infos;
        $brick_data['brick_commit_list'] = $brick_commit_list;
        $brick_data['brick_subject_list'] = $brick_subject_list;
        $brick_data['FunctionConnected'] = $functionlist->getdata();

        if($d->ei_git_brick_id) {
            $obj = $this->callClass(
                "Git", 
                "getBrickPath", 
                [
                    'ei_git_brick_id' => $d->ei_git_brick_id
                ]
            );

            $path =$obj->getData();
            $value['path'] = array_reverse($path);
        }
        
        $brick_data['path'] = $value;


        $this->setData($brick_data);
        
        return true;
    }




    /**
     * Recuperer les ifnormations d'un commit
     * 
     * @return array
     */
    function getCommitDetails()
    {
        $d = $this->checkParams(
            [
                'ei_git_commit_id' => 'string'
            ]
        ); 
        $s = $this->PDO->prepare(
            "SELECT
                *
            FROM
                ei_git_repo_subject_commit egrsc
            INNER JOIN
                ei_subject es on egrsc.commit_ei_subject_id=es.ei_subject_id AND  es.ei_subject_version_id = (SELECT 
                            MAX(s2.ei_subject_version_id)
                                FROM
                                    ei_subject s2
                                WHERE
                            s2.ei_subject_id = es.ei_subject_id)
            WHERE
                egrsc.ei_commit_id = :ei_git_commit_id"
        );
        $s->execute(
            [
                'ei_git_commit_id' => $d->ei_git_commit_id
            ]
        );

        $commit_infos = $s->fetchAll(PDO::FETCH_ASSOC);


        $s = $this->PDO->prepare(
            "SELECT
                *
            FROM
                ei_git_repo_subject_commit_git_brick egrscgb
            WHERE
                egrscgb.ei_commit_id = :ei_git_commit_id"
        );
        $s->execute(
            [
                'ei_git_commit_id' => $d->ei_git_commit_id
            ]
        );

        $commit_brick = $s->fetchAll(PDO::FETCH_ASSOC); 
        foreach ($commit_brick as $key => $value) {
            $obj = $this->callClass(
            "Git", 
            "getBrickPath", 
                [
                    'ei_git_brick_id' => $value['ei_git_brick_id']
                ]
            );

            $path =$obj->getData();
            $commit_brick[$key]['path'] = array_reverse($path);

            $s = $this->PDO->prepare(
                "SELECT 
                    brick_code
                FROM
                    ei_git_repo_subject_commit_git_brick
                WHERE
                    ei_git_brick_id = :ei_git_brick_id
                ORDER BY brick_datetime DESC
                LIMIT 2"
            );
            $s->execute(
                [
                    'ei_git_brick_id' => $value['ei_git_brick_id']
                ]
            );

            $commit_brick_code = $s->fetchAll(PDO::FETCH_ASSOC);
            $commit_brick[$key]['code'] = $commit_brick_code;
        }

        $s = $this->PDO->prepare(
            "SELECT
                *
            FROM
                ei_git_repo_subject_commit_git_file  egrscgf
            WHERE
                egrscgf.ei_commit_id = :ei_git_commit_id"
        );
        $s->execute(
            [
                'ei_git_commit_id' => $d->ei_git_commit_id
            ]
        );

        $commit_file = $s->fetchAll(PDO::FETCH_ASSOC);

        
         error_log(json_encode($commit_infos));

        $commit_data['commit_infos'] = $commit_infos;
        $commit_data['commit_brick'] = $commit_brick;
        $commit_data['commit_file'] = $commit_file;

        // error_log($commit_data);
        if (count($commit_infos) >=1) {
            $this->setData($commit_data);
        
            return true;  
        } else{
            return false;
        }

        
    }



    
    /**
     * Récupération du chemin de la fonction
     * 
     * @return array
     */
    function getBrickPath()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int'
            ]
        );

        // On récupère les infos de la fonction
        $s = $this->PDO->prepare(
            'SELECT 
                ft.ei_brick_tree_parent_node_id, f.ei_git_brick_name
            FROM
                ei_git_file_brick_tree ft,
                ei_git_file_brick f
            WHERE
                ft.ei_git_brick_id = f.ei_git_brick_id
                    AND ft.ei_git_brick_id =:ei_git_brick_id'
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );
        $res = $s->fetch(PDO::FETCH_ASSOC);

        $path = [];
        if($res['ei_brick_tree_parent_node_id']){
            $obj = $this->callClass(
                "Git", 
                "getBrickPathRecursive", 
                [
                    'parent_id' => $res['ei_brick_tree_parent_node_id'], 
                    'path' => $path
                ]
            ); 

            $this->setData($obj->getData());
        } else {
            $this->setData([]);
        }
        return true;
    }

        /**
     * Récupération récursive du chemin de la fonction
     * 
     * @return array
     */
    function getBrickPathRecursive()
    {
        $d = $this->checkParams(
            [
                'parent_id' => 'int',
                'path' => 'array'
            ]
        );

        // On regarde si le parent est une fonction
        $s = $this->PDO->prepare(
            'SELECT 
                ei_git_brick_id
            FROM
                ei_git_file_brick_tree
            WHERE
                ei_brick_tree_node_id =:parent_id'
        );
        $s->execute(
            [
                'parent_id' => $d->parent_id
            ]
        );
        $parent_function_id = (int)($s->fetch()?:[0])[0];

        $new_parent_id = 0;

        if ($parent_function_id == false) {
            // Le parent est un dossier, on récupère le nom du dossier et son parent id
            $s = $this->PDO->prepare(
                'SELECT 
                    ei_brick_tree_parent_node_id, foldername
                FROM
                    ei_git_file_brick_tree
                WHERE
                    ei_brick_tree_node_id =:parent_id'
            );
            $s->execute(
                [
                    'parent_id' => $d->parent_id
                ]
            );
            $res = $s->fetch(PDO::FETCH_ASSOC);

            $new_parent_id = $res['ei_brick_tree_parent_node_id'];

            array_push(
                $d->path, 
                [
                    'name' => $res['foldername'],
                    'type' => 'folder'
                ]
            );
        } else {
            // Le parent est une fonction
            $s = $this->PDO->prepare(
                'SELECT 
                    ft.ei_brick_tree_parent_node_id,
                    f.ei_git_brick_name,
                    ft.ei_git_brick_id
                FROM
                    ei_git_file_brick_tree ft,
                    ei_git_file_brick f
                WHERE
                    ft.ei_git_brick_id = f.ei_git_brick_id
                        AND ft.ei_brick_tree_node_id =:parent_id'
            );
            $s->execute(
                [
                    'parent_id' => $d->parent_id
                ]
            );
            $res = $s->fetch(PDO::FETCH_ASSOC);

            $new_parent_id = $res['ei_brick_tree_parent_node_id'];

            array_push(
                $d->path, 
                [
                    'name' => $res['ei_git_brick_name'],
                    'type' => 'Brick',
                    'function_id' => $res['ei_git_brick_id']
                ]
            );
        }

        $this->setData($d->path);

        // On vérifie ensuite que le parent a un parent si on retourne
        if ($new_parent_id != 0) {
            // Il reste des parents avant root
            $obj = $this->callClass(
                "Git", 
                "getBrickPathRecursive", 
                [
                    'parent_id' => $new_parent_id, 
                    'path' => $d->path
                ]
            );
            $d->path = $obj->getData();
        } else {
            // On a fini
            return true;
        }

        $this->setData($d->path);
    }


    /**
     * Connecter une brick a une fonction
     * 
     * @return array
     */
    function addFunctionInBrick()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int',
                'ei_function_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "INSERT INTO `ei_git_file_brick_function` (`ei_git_brick_id`, `ei_function_id`, `datetime`) VALUES (:ei_git_brick_id, :ei_function_id, now())"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id,
                'ei_function_id' => $d->ei_function_id
            ]
        );
        
        return true;
    }

    /**
     * Ajouter les brick d'un commit
     * 
     * @return array
     */
    function addCommitBrick()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_commit_id' => 'string',
                'git_file_array' => 'html',
                'ei_subject_id' => 'int'
            ]
        );
        $d->repo_id = $d->ei_git_repo_id;
        // error_log(json_encode($d->git_file_array));
        if ($d->git_file_array != '') {
            $git_file_array = json_decode($d->git_file_array);
            // error_log($d->git_file_array);
            foreach ($git_file_array as $git_file) {
                $git_file->status = 'modified';

                // Faire un call sql qui va verifier si le fichier existe deja dans la table ou non
                // s'il existe deja on get l'id sinon on le crée

                $s = $this->PDO->prepare(
                    "SELECT count(1) FROM ei_git_file where ei_git_file_path=:ei_git_file_path and ei_git_file_name=:ei_git_file_name and ei_git_repo_id=:ei_git_repo_id"
                );
                $s->execute(
                    [
                        'ei_git_file_name' => $git_file->file_name,
                        'ei_git_file_path' => $git_file->file_path,
                        'ei_git_repo_id' => $d->repo_id
                    ]
                );

                $countFileWithRepo = (int)($s->fetch()?:[0])[0];
                // error_log('laaa0');
                if ($countFileWithRepo == 0) {
                    error_log('file create');
                    /* Inserting the file information into the database. */
                    $s = $this->PDO->prepare(
                        "SELECT max(ei_git_file_id)+1 FROM ei_git_file;"
                    );
                    $s->execute([]);

                    $max_file_id = (int)($s->fetch()?:[0])[0];
                    if ($max_file_id == 0) {
                        $max_file_id = 1;
                    }


                    $s = $this->PDO->prepare(
                        "INSERT IGNORE INTO `ei_git_file` (`ei_git_repo_id`, `ei_git_file_id`, `ei_git_file_path`, `ei_git_file_name`) VALUES (:ei_git_repo_id, :ei_git_file_id, :ei_git_file_path, :ei_git_file_name);"
                    );
                    $s->execute(
                        [
                            'ei_git_file_id' => $max_file_id,
                            'ei_git_file_name' => $git_file->file_name,
                            'ei_git_file_path' => $git_file->file_path,
                            'ei_git_repo_id' => $d->repo_id
                        ]
                    );
                    // error_log(json_encode(explode(' ', $git_file->file_path)));
                    $fil_path_array = explode('/', $git_file->file_path);
                    // error_log(json_encode($fil_path_array));

                    $s = $this->PDO->prepare(
                        "SELECT ei_git_repo_name FROM ei_git_repo where ei_git_repo_id=:ei_git_repo_id;"
                    );
                    $s->execute(['ei_git_repo_id' => $d->repo_id]);

                    $repo_name = $s->fetch(PDO::FETCH_ASSOC);


                    $numItems = count($fil_path_array);
                    $i = 0;
                    foreach ($fil_path_array as $key => $value) {
                        // error_log($value);
                        // error_log($key);
                        ++$i;
                        // error_log('pas le dernier');
                        $s = $this->PDO->prepare(
                            "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:foldername and ei_git_id=:ei_git_id;"
                        );
                        $s->execute(
                            [
                                'foldername' => $value,
                                'ei_git_id' => $d->repo_id
                            ]
                        );
                        $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                        // error_log('ei_brick_tree_node_id '. $ei_brick_tree_node_id );
                        if ($ei_brick_tree_node_id) {
                            // error_log('existe deja');
                        } else {
                            // error_log('n existe pas');
                                // recuperation du ma node_id pour la brick 
                            $s = $this->PDO->prepare(
                                "SELECT max(ei_brick_tree_node_id)+1 as max_node_id from ei_git_file_brick_tree"
                            );
                            $s->execute([]);

                            $max_node_brick_id_tree = (int)($s->fetch()?:[0])[0];
                            if ($max_node_brick_id_tree == 0) {
                                $max_node_brick_id_tree = 1;
                            }

                            
                            // error_log('i value : ' . $i);
                            if ($i === 1) {
                                $s = $this->PDO->prepare(
                                    "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:repo_name and ei_git_id=:ei_git_id;"
                                );
                                $s->execute(
                                    [
                                        'repo_name' => $repo_name['ei_git_repo_name'],
                                        'ei_git_id' => $d->repo_id
                                    ]
                                );
                                $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                                error_log($repo_name['ei_git_repo_name']);
                            } else {
                                $s = $this->PDO->prepare(
                                    "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:foldername and ei_git_id=:ei_git_id;"
                                );
                                $s->execute(
                                    [
                                        'foldername' => $fil_path_array[$key - 1],
                                        'ei_git_id' => $d->repo_id
                                    ]
                                );
                                $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);
                            }

                            // recuperation de max pos id 

                            $s = $this->PDO->prepare(
                                "SELECT max(position)+1 as max_pos_node_id from ei_git_file_brick_tree where ei_brick_tree_parent_node_id=:ei_brick_tree_parent_node_id"
                            );
                            $s->execute(
                                [
                                    'ei_brick_tree_parent_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                ]
                            );

                            $max_pos_node_id_tree = (int)($s->fetch()?:[0])[0];
                            if ($max_pos_node_id_tree == 0) {
                                $max_pos_node_id_tree = 1;
                            }

                            $s = $this->PDO->prepare(
                                "INSERT IGNORE INTO `ei_git_file_brick_tree` (`ref_object_familly_id`, `ei_brick_tree_parent_node_id`, `ei_brick_tree_node_id`, `position`, foldername,`ei_git_brick_id`, `showed`, ei_git_id) 
                                VALUES ('BRK', :ei_brick_tree_node_id, :max_node_brick_id, :max_pos_node_id,:foldername ,null, 'Y', :ei_git_id);"
                            );
                            $s->execute(
                                [
                                    'foldername' => str_replace('"', '', $value),
                                    'max_node_brick_id' => $max_node_brick_id_tree,
                                    'max_pos_node_id' => $max_pos_node_id_tree,
                                    'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id'],
                                    'ei_git_id' => $d->repo_id
                                ]
                            );

                        }
                    }
                } else {
                    $s = $this->PDO->prepare(
                        "SELECT ei_git_file_id FROM ei_git_file where ei_git_file_path=:ei_git_file_path and ei_git_file_name=:ei_git_file_name and ei_git_repo_id=:ei_git_repo_id"
                    );
                    $s->execute(
                        [
                            'ei_git_file_name' => $git_file->file_name,
                            'ei_git_file_path' => $git_file->file_path,
                            'ei_git_repo_id' => $d->repo_id
                        ]
                    );
                    $max_file_id = (int)($s->fetch()?:[0])[0];
                }
                

                $s = $this->PDO->prepare(
                    "INSERT IGNORE INTO `ei_git_repo_subject_commit_git_file` (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `ei_git_file_id`, `action`) 
                    VALUES (:ei_git_repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_git_file_id, :action);"
                );
                $s->execute(
                    [
                        'ei_git_repo_subject_branch_id' => 0,
                        'ei_commit_id' =>  $d->ei_commit_id,
                        'ei_git_file_id' => $max_file_id,
                        'ei_git_repo_id' => $d->repo_id,
                        'action' => 'modified'
                    ]
                );
                if (array_key_exists('commit_bricks', $git_file)) {
                    $git_file_array_brick = $git_file->commit_bricks;
                    // error_log('file_changes : ' . $git_file->file_name);
                    foreach ($git_file_array_brick as $git_brick) {


                        // Verifier si la brick existe deja dans la table ou non

                        $s = $this->PDO->prepare(
                            "SELECT count(1) FROM ei_git_file_brick where ei_git_brick_type=:ei_git_brick_type and ei_git_brick_name=:ei_git_brick_name and ei_git_repo_id=:ei_git_repo_id and ei_git_file_id=:ei_git_file_id"
                        );
                        $s->execute(
                            [
                                'ei_git_brick_type' => $git_brick->type,
                                'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                'ei_git_repo_id' => $d->repo_id,
                                'ei_git_file_id' => $max_file_id,
                            ]
                        );

                        $countFileBrick = (int)($s->fetch()?:[0])[0];
                        error_log( $countFileBrick);
                        if ($countFileBrick == 0) {
                            error_log('count file brick 0');
                            /* Inserting the new brick into the database. */
                            $s = $this->PDO->prepare(
                                "SELECT max(ei_git_brick_id)+1 FROM ei_git_file_brick;"
                            );
                            $s->execute([]);

                            $max_brick_id = (int)($s->fetch()?:[0])[0];
                            if ($max_brick_id == 0) {
                                $max_brick_id = 1;
                            }

                            switch ($git_brick->type) {
                            case 'class':
                                $name_class = str_replace('"', '', $git_brick->name);
                                $name_function = '';
                                $name_method = '';
                                break;
                            case 'method':
                                $name_class = '';
                                $name_function = '';
                                $name_method = str_replace('"', '', $git_brick->name);
                                break;
                            default:
                                $name_class = '';
                                $name_function = str_replace('"', '', $git_brick->name);
                                $name_method = '';
                                break;

                            }

                            $s = $this->PDO->prepare(
                                "INSERT IGNORE INTO `ei_git_file_brick` 
                                (`ei_git_repo_id`, `ei_git_file_id`, `ei_git_brick_id`, `ei_git_brick_name`, `ei_git_brick_type`, `class_name`, `class_methode_name`, `function_name`) 
                                VALUES (:ei_git_repo_id, :ei_git_file_id, :ei_git_brick_id, :ei_git_brick_name, :ei_git_brick_type, :name_class, :name_method, :name_function);"
                            );
                            $s->execute(
                                [
                                    'ei_git_brick_id' => $max_brick_id,
                                    'ei_git_brick_type' => $git_brick->type,
                                    'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                    'ei_git_repo_id' => $d->repo_id,
                                    'ei_git_file_id' => $max_file_id,
                                    'name_class' => $name_class,
                                    'name_function' => $name_function,
                                    'name_method' => $name_method
                                ]
                            );

                            $this->callClass(
                                "Git", 
                                "addSubjectBrickLink", 
                                [
                                    'ei_git_brick_id' => $max_brick_id,
                                    'ei_subject_id' => $d->ei_subject_id
                                ]
                            );


                            // recuperation du ma node_id pour la brick 

                            $s = $this->PDO->prepare(
                                "SELECT max(ei_brick_tree_node_id)+1 as max_node_id from ei_git_file_brick_tree"
                            );
                            $s->execute([]);

                            $max_node_brick_id = (int)($s->fetch()?:[0])[0];
                            if ($max_node_brick_id == 0) {
                                $max_node_brick_id = 1;
                            }

                        
                            $s = $this->PDO->prepare(
                                "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where foldername=:repo_name and ei_git_id=:ei_git_id;"
                            );
                            $s->execute(
                                [
                                    'repo_name' => $git_file->file_name,
                                    'ei_git_id' => $d->repo_id
                                ]
                            );
                            $ei_brick_tree_node_id = $s->fetch(PDO::FETCH_ASSOC);

                            $s = $this->PDO->prepare(
                                "SELECT max(position)+1 as max_pos_node_id from ei_git_file_brick_tree where ei_brick_tree_parent_node_id=:ei_brick_tree_node_id"
                            );
                            $s->execute(
                                [
                                    'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                ]
                            );

                            $max_pos_node_id = (int)($s->fetch()?:[0])[0];
                            if ($max_pos_node_id == 0) {
                                $max_pos_node_id = 1;
                            }

                            
                            switch ($git_brick->type) {
                            case 'class':
                                    //recuperer le ei_brick_tree_node_id si le dossier existe deja 
                                    $s = $this->PDO->prepare(
                                        "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where  ei_brick_tree_node_id=:ei_brick_tree_node_id"
                                    );

                                    $s->execute(
                                        [
                                            'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                        ]
                                    );

                                    $tree_node_id = (int)($s->fetch()?:[0])[0];
                                    $tree_node_familly_type = 'BRKC';


                                
                                break;
                            case 'method':

                                // verifier si le dossier class existe deja dans le parent
                                $s = $this->PDO->prepare(
                                    "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where ref_object_familly_id='BRKC' and ei_brick_tree_parent_node_id=:ei_brick_tree_node_id"
                                );
                                $s->execute(
                                    [
                                        'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                    ]
                                );

                                
                                $tree_node_id = (int)($s->fetch()?:[0])[0];
                                $tree_node_familly_type = 'BRKM';
                                break;
                            default:

                                
                                    //recuperer le ei_brick_tree_node_id si le dossier existe deja 
                                    $s = $this->PDO->prepare(
                                        "SELECT ei_brick_tree_node_id FROM ei_git_file_brick_tree where  ei_brick_tree_node_id=:ei_brick_tree_node_id"
                                    );

                                    $s->execute(
                                        [
                                            'ei_brick_tree_node_id' => $ei_brick_tree_node_id['ei_brick_tree_node_id']
                                        ]
                                    );

                                    $tree_node_id = (int)($s->fetch()?:[0])[0];

                                $tree_node_familly_type = 'BRKF';
                                break;

                            }




                            // ajout des nodes dans l'arbre des brick
                            $s = $this->PDO->prepare(
                                "INSERT IGNORE INTO `ei_git_file_brick_tree` (`ref_object_familly_id`, `ei_brick_tree_parent_node_id`, `ei_brick_tree_node_id`, `position`, `ei_git_brick_id`, `showed`, ei_git_id) 
                                VALUES (:tree_node_familly_type, :ei_brick_tree_node_id, :max_node_brick_id, :max_pos_node_id, :ei_git_brick_id, 'Y', :ei_git_id);"
                            );
                            $s->execute(
                                [
                                    'ei_git_brick_id' => $max_brick_id,
                                    'ei_brick_tree_node_id' => $tree_node_id,
                                    'max_node_brick_id' => $max_node_brick_id,
                                    'max_pos_node_id' => $max_pos_node_id,
                                    'tree_node_familly_type' => $tree_node_familly_type,
                                    'ei_git_id' => $d->repo_id
                                ]
                            );



                        } else {
                            $s = $this->PDO->prepare(
                                "SELECT ei_git_brick_id FROM ei_git_file_brick where ei_git_brick_type=:ei_git_brick_type and ei_git_brick_name=:ei_git_brick_name and ei_git_repo_id=:ei_git_repo_id and ei_git_file_id=:ei_git_file_id"
                            );
                            $s->execute(
                                [
                                    'ei_git_brick_type' => $git_brick->type,
                                    'ei_git_brick_name' => str_replace('"', '', $git_brick->name),
                                    'ei_git_repo_id' => $d->repo_id,
                                    'ei_git_file_id' => $max_file_id,
                                ]
                            );
                            $max_brick_id = (int)($s->fetch()?:[0])[0];
                        }

                        $s = $this->PDO->prepare(
                            "INSERT IGNORE INTO `ei_git_repo_subject_commit_git_brick` 
                            (`ei_git_repo_id`, `ei_git_repo_subject_branch_id`, `ei_commit_id`, `ei_git_file_id`, `ei_git_brick_id`,brick_code,brick_datetime, `action`) 
                            VALUES (:ei_git_repo_id, :ei_git_repo_subject_branch_id, :ei_commit_id, :ei_git_file_id, :ei_git_brick_id,:brick_code,now(),:action);"
                        );
                        $s->execute(
                            [
                                'ei_commit_id' =>  $d->ei_commit_id,
                                'ei_git_brick_id' => $max_brick_id,
                                'action' => $git_brick->status || 'modified',
                                'ei_git_repo_id' => $d->repo_id,
                                'ei_git_repo_subject_branch_id' => 0,
                                'brick_code' => $git_brick->code,
                                'ei_git_file_id' => $max_file_id,
                            ]
                        );
                    }
                }
                
            }

            // $s = $this->PDO->prepare(
            //     "INSERT INTO `ei_git_file_brick_function` (`ei_git_brick_id`, `ei_function_id`, `datetime`) VALUES (:ei_git_brick_id, :ei_function_id, now())"
            // );
            // $s->execute(
            //     [
            //         'ei_git_brick_id' => $d->ei_git_brick_id,
            //         'ei_function_id' => $d->ei_function_id
            //     ]
            // );
        
        }

        // error_log('la ???');
        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_subject_commit` 
            SET 
                `commit_brick_sync` = now()
            WHERE
                `ei_git_repo_id` = :ei_git_repo_id
                    AND `ei_commit_id` = :ei_commit_id
            "
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_commit_id' => $d->ei_commit_id,
            ]
        );
        return true;
    }

    /**
     * Connecter un subject a un commit
     * 
     * @return array
     */
    function assignSubjectToCommit()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_git_repo_subject_branch_id' => 'int',
                'ei_commit_id' => 'string',
                'commit_ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_subject_commit` 
            SET 
                `commit_ei_subject_id` = :commit_ei_subject_id
            WHERE
                `ei_git_repo_id` = :ei_git_repo_id
                    AND `ei_git_repo_subject_branch_id` = :ei_git_repo_subject_branch_id
                    AND `ei_commit_id` = :ei_commit_id;
            "
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_git_repo_subject_branch_id' => $d->ei_git_repo_subject_branch_id,
                'ei_commit_id' => $d->ei_commit_id,
                'commit_ei_subject_id' => $d->commit_ei_subject_id
            ]
        );
        
        return true;
    }

    /**
     * Connecter un subject a un commit history
     * 
     * @return array
     */
    function assignSubjectToCommitHistory()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_commit_id' => 'string',
                'commit_ei_subject_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_subject_commit` 
            SET 
                `commit_ei_subject_id` = :commit_ei_subject_id
            WHERE
                `ei_git_repo_id` = :ei_git_repo_id
                    AND `ei_git_repo_subject_branch_id` = 0
                    AND `ei_commit_id` = :ei_commit_id;
            "
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_commit_id' => $d->ei_commit_id,
                'commit_ei_subject_id' => $d->commit_ei_subject_id
            ]
        );
        
        return true;
    }

    /**
     * Créé un subject connecter a un commit qui se trouvera sur la delivery qui correspond a la date du commit
     * 
     * @return array
     */
    function createAutoSubjectCommitToDeliveryDate()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_commit_id' => 'string',
                'commit_datetime' => 'string',
                'commit_name' => 'html'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                *
            FROM
                ei_delivery
            WHERE
                delivery_date < :commit_datetime
            ORDER BY delivery_date DESC
            LIMIT 1"
        );

        $s->execute(
            [
                'commit_datetime' => $d->commit_datetime
            ]
        );


        $delivryOnSubject = $s->fetch(PDO::FETCH_ASSOC);
        $s2 = $this->PDO->prepare(
            "SELECT 
                ref_subject_status_id
            FROM
                ref_subject_status
            WHERE
                is_final = 'Y'
            LIMIT 1"
        );

        $s2->execute([]);


        $firstStatusIsFinal = $s2->fetch(PDO::FETCH_ASSOC);
        // $subject = 
        $subject = $this->callClass(
            "Core", 
            "createNewSubject", 
            [
                'title' => $d->commit_name,
                'delivery_id'=>$delivryOnSubject['ei_delivery_id'],
                'type_id'=>'1',
                'priority_id'=>'1',
                'in_charge_id'=>0,
                'status_id'=>$firstStatusIsFinal['ref_subject_status_id'],
                'description'=>$d->commit_name,
                'pool_id' => '1',
                'ei_subject_external_id'=>'',
                'risk_list'=>[]
            ]
        );
        $subjectId = $subject->getData();

        $this->setData($subjectId);
        return true;
    }

    /**
     * Relier tout les commit a des subject si possible
     * 
     * @return array
     */
    function assignSubjectToCommitHistoryBulk()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'commit_list' => 'html'
            ]
        );

        $pattern = "/S (\d+) -/";

        foreach ($d->commit_list as  $git_commit) {
            if (preg_match($pattern, $git_commit->commit_message, $matches)) {
                $subjectId = $matches[1];
                // $subjectId contient maintenant l'identifiant extrait
                // error_log("Identifiant extrait : " . $subjectId);
                // error_log($git_commit->ei_commit_id);
                // error_log($d->ei_git_repo_id);
                $s = $this->PDO->prepare(
                    "UPDATE `ei_git_repo_subject_commit` 
                    SET 
                        `commit_ei_subject_id` = :commit_ei_subject_id
                    WHERE
                        `ei_git_repo_id` = :ei_git_repo_id
                            AND `ei_git_repo_subject_branch_id` = 0
                            AND `ei_commit_id` = :ei_commit_id;
                    "
                );
                $s->execute(
                    [
                        'ei_git_repo_id' => $d->ei_git_repo_id,
                        'ei_commit_id' => $git_commit->ei_commit_id,
                        'commit_ei_subject_id' => $subjectId
                    ]
                );
            } else {
                error_log("Aucun identifiant trouvé dans le message de commit.");
            }
            // $value->name
            // $s = $this->PDO->prepare(
            //     ""
            // );
            // $s->execute(
            //     []
            // );
        }

        
        
        return true;
    }

    /**
     * Ignorer un commit history
     * 
     * @return array
     */
    function ignoreCommitHistory()
    {
        $d = $this->checkParams(
            [
                'ei_git_repo_id' => 'int',
                'ei_commit_id' => 'string',
                'commit_ignored' => 'string'
            ]
        );

        $s = $this->PDO->prepare(
            "UPDATE `ei_git_repo_subject_commit` SET `commit_ignored`=:commit_ignored
            WHERE `ei_git_repo_id`=:ei_git_repo_id
            and`ei_git_repo_subject_branch_id`='0' 
            and`ei_commit_id`=:ei_commit_id;

            "
        );
        $s->execute(
            [
                'ei_git_repo_id' => $d->ei_git_repo_id,
                'ei_commit_id' => $d->ei_commit_id,
                'commit_ignored' => $d->commit_ignored
            ]
        );
        
        return true;
    }

    /**
     * Recuperer les infos des fonction relier a une brick
     * 
     * @return array
     */
    function getBrickFunctionList()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int'
            ]
        );

        $s = $this->PDO->prepare(
            "SELECT 
                egfbf.*, egfb.*, ef.*
            FROM
                ei_git_file_brick_function egfbf
                    INNER JOIN
                ei_git_file_brick egfb ON egfb.ei_git_brick_id = egfbf.ei_git_brick_id
                    INNER JOIN
                ei_function ef ON ef.ei_function_id = egfbf.ei_function_id
            WHERE
                egfbf.ei_git_brick_id = :ei_git_brick_id"
        );
        $s->execute(
            [
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );

        $functionlist = $s->fetchAll(PDO::FETCH_ASSOC);

        foreach ($functionlist as $key => $value) {
            
            $obj = $this->callClass(
                "Functions", 
                "getPath", 
                [
                    'ei_function_id' =>$value['ei_function_id']
                ]
            );

            $path =$obj->getData();
            $value['path'] = array_reverse($path);
            // error_log(json_encode($value));
            $functionlist[$key] = $value;
        }

        $this->setData($functionlist);
        
        return true;
    }

    /**
     * Recuperer les impact du subject courant et du subject du commit
     * 
     * @return array
     */
    function getFunctionImpactedSubjectBrick()
    {
        $d = $this->checkParams(
            [
                'ei_git_brick_id' => 'int'
            ]
        );
        $currentSubject = $this->callClass(
            "User", 
            "getCurrentSubjectId",
            [
                'null' => 'null',
            ]
        );

        $ei_subject_id = $currentSubject->getdata();

        $s = $this->PDO->prepare(
            "SELECT 
                esr.*,ei.*,efp.*
            FROM
                ei_git_file_brick egfb
                    INNER JOIN
                ei_git_repo_subject_commit_git_brick egrscgb ON egrscgb.ei_git_brick_id = egfb.ei_git_brick_id
                    INNER JOIN
                ei_git_repo_subject_commit egrsc ON egrsc.ei_commit_id = egrscgb.ei_commit_id
                    INNER JOIN
                ei_user eu ON eu.ei_user_id = egrsc.commit_user_id
                    INNER JOIN
                ei_git_repo_subject_branch esb ON esb.ei_git_repo_subject_branch_id = egrsc.ei_git_repo_subject_branch_id
                    INNER JOIN
                ei_subject_risk esr ON esr.ei_subject_id = esb.ei_subject_id
                    INNER JOIN
                ei_function ei ON ei.ei_function_id = esr.ei_function_id
                    INNER JOIN
                ei_function_path_vw efp ON ei.ei_function_id = efp.ei_function_id
            WHERE
                egfb.ei_git_brick_id = :ei_git_brick_id 
            UNION SELECT 
                esr2.*,ei.*,efp.*
            FROM
                ei_git_file_brick egfb
                    INNER JOIN
                ei_git_repo_subject_commit_git_brick egrscgb ON egrscgb.ei_git_brick_id = egfb.ei_git_brick_id
                    INNER JOIN
                ei_git_repo_subject_commit egrsc ON egrsc.ei_commit_id = egrscgb.ei_commit_id
                    INNER JOIN
                ei_user eu ON eu.ei_user_id = egrsc.commit_user_id
                    INNER JOIN
                ei_git_repo_subject_branch esb ON esb.ei_git_repo_subject_branch_id = egrsc.ei_git_repo_subject_branch_id
                    INNER JOIN
                ei_subject_risk esr2 ON esr2.ei_subject_id = :ei_subject_id
                    INNER JOIN
                ei_function ei ON ei.ei_function_id = esr2.ei_function_id
                    INNER JOIN
                ei_function_path_vw efp ON ei.ei_function_id = efp.ei_function_id
            WHERE
                egfb.ei_git_brick_id = :ei_git_brick_id"
        );
        $s->execute(
            [
                'ei_subject_id' => $ei_subject_id['subject_id'],
                'ei_git_brick_id' => $d->ei_git_brick_id
            ]
        );

        $functionlist = $s->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($functionlist);
        
        return true;
    }
}