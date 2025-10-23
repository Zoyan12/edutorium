create table public.battle_records (
  id serial not null,
  battle_id uuid not null default gen_random_uuid (),
  player1_id uuid not null,
  player1_name character varying(100) not null,
  player1_initial_points integer null default 0,
  player1_final_points integer null default 0,
  player2_id uuid not null,
  player2_name character varying(100) not null,
  player2_initial_points integer null default 0,
  player2_final_points integer null default 0,
  start_time timestamp with time zone not null default now(),
  end_time timestamp with time zone null,
  duration_seconds integer null,
  battle_type character varying(20) null default 'Success'::character varying,
  battle_result character varying(20) null default 'Incomplete'::character varying,
  questions_count integer not null default 5,
  difficulty character varying(20) not null default 'medium'::character varying,
  subject character varying(50) not null default 'general'::character varying,
  player1_correct_answers integer null default 0,
  player2_correct_answers integer null default 0,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  battle_mode character varying(20) not null default 'arena'::character varying,
  constraint battle_records_pkey primary key (id),
  constraint battle_records_battle_id_key unique (battle_id),
  constraint battle_records_player1_id_fkey foreign KEY (player1_id) references auth.users (id),
  constraint battle_records_player2_id_fkey foreign KEY (player2_id) references auth.users (id),
  constraint battle_records_battle_type_check check (
    (
      (battle_type)::text = any (
        (
          array[
            'Success'::character varying,
            'Cancelled'::character varying,
            'Player1Left'::character varying,
            'Player2Left'::character varying,
            'Timeout'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint battle_records_battle_mode_check check (
    (
      (battle_mode)::text = any (
        array[
          ('arena'::character varying)::text,
          ('quick'::character varying)::text,
          ('Success'::character varying)::text,
          ('Cancelled'::character varying)::text,
          ('Player1Left'::character varying)::text,
          ('Player2Left'::character varying)::text,
          ('Timeout'::character varying)::text
        ]
      )
    )
  ),
  constraint battle_records_battle_result_check check (
    (
      (battle_result)::text = any (
        (
          array[
            'Draw'::character varying,
            'Player1Wins'::character varying,
            'Player2Wins'::character varying,
            'Incomplete'::character varying
          ]
        )::text[]
      )
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_battle_records_battle_id on public.battle_records using btree (battle_id) TABLESPACE pg_default;

create index IF not exists idx_battle_records_player1_id on public.battle_records using btree (player1_id) TABLESPACE pg_default;

create index IF not exists idx_battle_records_player2_id on public.battle_records using btree (player2_id) TABLESPACE pg_default;

create index IF not exists idx_battle_records_start_time on public.battle_records using btree (start_time) TABLESPACE pg_default;

create index IF not exists idx_battle_records_battle_type on public.battle_records using btree (battle_type) TABLESPACE pg_default;

create index IF not exists idx_battle_records_battle_result on public.battle_records using btree (battle_result) TABLESPACE pg_default;

create index IF not exists idx_battle_records_battle_mode on public.battle_records using btree (battle_mode) TABLESPACE pg_default;

create trigger update_battle_records_timestamp BEFORE
update on battle_records for EACH row
execute FUNCTION update_battle_records_updated_at ();

create trigger calculate_battle_duration_trigger BEFORE
update on battle_records for EACH row
execute FUNCTION calculate_battle_duration ();

create trigger set_battle_mode_trigger BEFORE INSERT
or
update on battle_records for EACH row
execute FUNCTION set_battle_mode_from_type ();

create trigger quick_battle_questions_trigger
after INSERT on battle_records for EACH row
execute FUNCTION auto_create_quick_battle_questions ();