# Unduplicator-w-B24

Перед запуском необходимо создать директорию для логирования по пути из корня: `./logs/` и два файла логирования с правами `755`: 
* `app.log` 
* `conflicts.log`

Объединятор кастомный:
1. Получение комментов `crm.timeline.comment.list`
   ```
    "filter": {
        "ENTITY_ID": lead_id,
        "ENTITY_TYPE": "lead"
    },
    "order": {
        "CREATED": "DESC"
    }

2. Дела, смски, звонки, недозвоны `crm.activity.list`
    ```
    "filter": {
       "OWNER_TYPE_ID": 1,     //entityTypeId
       "OWNER_ID": 1242007   //entityId
    },
    "order": {
       "created": "desc"
    },
    "start": 0

3. Звонок начало `telephony.externalCall.register`
   ```
   "USER_ID": 3201,
   "PHONE_NUMBER": "79326216120",
   "TYPE": 1,
   "CRM_CREATE": 0,
   "CRM_ENTITY_TYPE": "LEAD",
   "CRM_ENTITY_ID": 754688

4. Звонок конец `telephony.externalCall.finish`
   ```
   "CALL_ID": "externalCall.025f5a2169ac3e735d7c1843a22d7101.1778675003",
   "USER_ID": 3201,
   "DURATION": 50,
   "STATUS_CODE": "480",
   "FAILED_REASON" : "но",
   "ADD_TO_CHAT": 0
   
5. Прикрепление записи походу придётся делать через скачивание файла во временную директорию и потом передача, после передачи удаление этого файла