<?php

namespace App\Modules\Scheduling\Domain;

interface ScheduleRepository
{
    /** @return list<CourseSession> */
    public function teacherSessions(int $teacherId, ?int $exceptOfferingId = null, ?int $exceptSessionId = null): array;

    /** @return list<CourseSession> */
    public function classroomSessions(int $classroomId, ?int $exceptOfferingId = null, ?int $exceptSessionId = null): array;

    /** @return list<CourseSession> */
    public function studentActiveSessions(int $studentId, ?int $exceptCourseId = null): array;

    /**
     * @param  array{course_id:int,group_code:string,starts_on:string,ends_on:string,max_students:int,modality:string,status:string}  $data
     * @param  list<int>  $teacherIds
     * @param  list<array{title:string,session_date:string,starts_at:string,ends_at:string,teacher_id:int,classroom_id?:?int,meeting_url?:?string,status?:string}>  $sessions
     */
    public function create(array $data, array $teacherIds, array $sessions): int;

    /**
     * @param  array{course_id:int,group_code:string,starts_on:string,ends_on:string,max_students:int,modality:string,status:string}  $data
     * @param  list<int>  $teacherIds
     * @param  list<array{id?:?int,title:string,session_date:string,starts_at:string,ends_at:string,teacher_id:int,classroom_id?:?int,meeting_url?:?string,status?:string}>  $sessions
     */
    public function update(int $offeringId, array $data, array $teacherIds, array $sessions): void;

    public function delete(int $offeringId): void;

    /**
     * @param  array{title:string,session_date:string,starts_at:string,ends_at:string,teacher_id:int,classroom_id?:?int,meeting_url?:?string,status?:string}  $data
     */
    public function saveSession(int $offeringId, array $data, ?int $sessionId = null): int;

    public function deleteSession(int $sessionId): void;

    /** @return array{id:int,course_id:int,modality:string,teacher_ids:list<int>}|null */
    public function findOfferingMeta(int $offeringId): ?array;

    public function offeringIdOfSession(int $sessionId): int;

    public function syncCourseTeachers(int $courseId): void;

    public function courseIdOf(int $offeringId): int;

    /** @param array{name:string,capacity:?int,is_active:bool} $data */
    public function saveClassroom(array $data, ?int $classroomId = null): int;

    public function deleteClassroom(int $classroomId): void;

    public function classroomHasSchedules(int $classroomId): bool;
}
