-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.battle_records (
  id integer NOT NULL DEFAULT nextval('battle_records_id_seq'::regclass),
  battle_id uuid NOT NULL DEFAULT gen_random_uuid() UNIQUE,
  player1_id uuid NOT NULL,
  player1_name character varying NOT NULL,
  player1_initial_points integer DEFAULT 0,
  player1_final_points integer DEFAULT 0,
  player2_id uuid NOT NULL,
  player2_name character varying NOT NULL,
  player2_initial_points integer DEFAULT 0,
  player2_final_points integer DEFAULT 0,
  start_time timestamp with time zone NOT NULL DEFAULT now(),
  end_time timestamp with time zone,
  duration_seconds integer,
  battle_type character varying DEFAULT 'Success'::character varying CHECK (battle_type::text = ANY (ARRAY['Success'::character varying::text, 'Cancelled'::character varying::text, 'Player1Left'::character varying::text, 'Player2Left'::character varying::text, 'Timeout'::character varying::text])),
  battle_result character varying DEFAULT 'Incomplete'::character varying CHECK (battle_result::text = ANY (ARRAY['Draw'::character varying::text, 'Player1Wins'::character varying::text, 'Player2Wins'::character varying::text, 'Incomplete'::character varying::text])),
  questions_count integer NOT NULL DEFAULT 5,
  difficulty character varying NOT NULL DEFAULT 'medium'::character varying,
  subject character varying NOT NULL DEFAULT 'general'::character varying,
  player1_correct_answers integer DEFAULT 0,
  player2_correct_answers integer DEFAULT 0,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  battle_mode character varying NOT NULL DEFAULT 'arena'::character varying CHECK (battle_mode::text = ANY (ARRAY['arena'::character varying::text, 'quick'::character varying::text, 'Success'::character varying::text, 'Cancelled'::character varying::text, 'Player1Left'::character varying::text, 'Player2Left'::character varying::text, 'Timeout'::character varying::text])),
  is_rematch boolean DEFAULT false,
  original_battle_id uuid,
  CONSTRAINT battle_records_pkey PRIMARY KEY (id),
  CONSTRAINT battle_records_player1_id_fkey FOREIGN KEY (player1_id) REFERENCES auth.users(id),
  CONSTRAINT battle_records_player2_id_fkey FOREIGN KEY (player2_id) REFERENCES auth.users(id)
);
CREATE TABLE public.battle_responses (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  battle_id uuid NOT NULL,
  player_id uuid NOT NULL,
  question_id integer NOT NULL,
  answer_given text,
  is_skipped boolean DEFAULT false,
  response_time_ms integer,
  is_correct boolean,
  points_awarded integer,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT battle_responses_pkey PRIMARY KEY (id),
  CONSTRAINT battle_responses_battle_id_fkey FOREIGN KEY (battle_id) REFERENCES public.battle_records(battle_id)
);
CREATE TABLE public.friend_relationships (
  id bigint GENERATED ALWAYS AS IDENTITY NOT NULL,
  user_id uuid NOT NULL,
  friend_id uuid NOT NULL,
  status text NOT NULL CHECK (status = ANY (ARRAY['pending'::text, 'accepted'::text])),
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  CONSTRAINT friend_relationships_pkey PRIMARY KEY (id),
  CONSTRAINT friend_relationships_friend_id_fkey FOREIGN KEY (friend_id) REFERENCES public.profiles(user_id),
  CONSTRAINT friend_relationships_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(user_id)
);
CREATE TABLE public.maintenance_mode (
  id integer NOT NULL DEFAULT nextval('maintenance_mode_id_seq'::regclass),
  is_active boolean NOT NULL DEFAULT false,
  start_time timestamp with time zone NOT NULL DEFAULT now(),
  duration_minutes integer NOT NULL DEFAULT 60,
  started_by uuid,
  reason text NOT NULL,
  user_message text NOT NULL,
  expected_resolution text NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  updated_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT maintenance_mode_pkey PRIMARY KEY (id),
  CONSTRAINT maintenance_mode_started_by_fkey FOREIGN KEY (started_by) REFERENCES auth.users(id)
);
CREATE TABLE public.profiles (
  id integer NOT NULL DEFAULT nextval('profiles_id_seq'::regclass),
  user_id uuid NOT NULL UNIQUE,
  username text UNIQUE,
  full_name text,
  avatar_url text,
  phone text,
  location text,
  education text,
  board text,
  field text,
  is_complete boolean DEFAULT false,
  points integer DEFAULT 0,
  last_seen timestamp with time zone,
  wins integer DEFAULT 0,
  streak integer DEFAULT 0,
  battles integer DEFAULT 0,
  is_admin boolean DEFAULT false,
  CONSTRAINT profiles_pkey PRIMARY KEY (id),
  CONSTRAINT profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id)
);
CREATE TABLE public.questions (
  id integer NOT NULL DEFAULT nextval('questions_id_seq'::regclass),
  image_url text NOT NULL,
  correct_answer character NOT NULL CHECK (correct_answer = ANY (ARRAY['A'::bpchar, 'B'::bpchar, 'C'::bpchar, 'D'::bpchar])),
  subject character varying,
  difficulty character varying DEFAULT 'medium'::character varying CHECK (difficulty::text = ANY (ARRAY['easy'::character varying::text, 'medium'::character varying::text, 'hard'::character varying::text])),
  created_by uuid,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT questions_pkey PRIMARY KEY (id),
  CONSTRAINT questions_created_by_fkey FOREIGN KEY (created_by) REFERENCES auth.users(id)
);
CREATE TABLE public.quick_battle_questions (
  id integer NOT NULL DEFAULT nextval('quick_battle_questions_id_seq'::regclass),
  battle_id uuid NOT NULL UNIQUE,
  question1_id integer NOT NULL,
  question2_id integer NOT NULL,
  question3_id integer NOT NULL,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT quick_battle_questions_pkey PRIMARY KEY (id),
  CONSTRAINT quick_battle_questions_battle_id_fkey FOREIGN KEY (battle_id) REFERENCES public.battle_records(battle_id),
  CONSTRAINT quick_battle_questions_question1_id_fkey FOREIGN KEY (question1_id) REFERENCES public.questions(id),
  CONSTRAINT quick_battle_questions_question2_id_fkey FOREIGN KEY (question2_id) REFERENCES public.questions(id),
  CONSTRAINT quick_battle_questions_question3_id_fkey FOREIGN KEY (question3_id) REFERENCES public.questions(id)
);
CREATE TABLE public.quick_battle_responses (
  id integer NOT NULL DEFAULT nextval('quick_battle_responses_id_seq'::regclass),
  battle_id uuid NOT NULL,
  player_id uuid NOT NULL,
  question_id integer NOT NULL,
  answer_given character CHECK (answer_given IS NULL OR (answer_given = ANY (ARRAY['A'::bpchar, 'B'::bpchar, 'C'::bpchar, 'D'::bpchar]))),
  is_correct boolean NOT NULL DEFAULT false,
  is_skipped boolean NOT NULL DEFAULT false,
  points_awarded integer NOT NULL DEFAULT 0,
  time_taken_ms integer,
  answered_at timestamp with time zone DEFAULT now(),
  CONSTRAINT quick_battle_responses_pkey PRIMARY KEY (id),
  CONSTRAINT quick_battle_responses_battle_id_fkey FOREIGN KEY (battle_id) REFERENCES public.battle_records(battle_id),
  CONSTRAINT quick_battle_responses_player_id_fkey FOREIGN KEY (player_id) REFERENCES auth.users(id),
  CONSTRAINT quick_battle_responses_question_id_fkey FOREIGN KEY (question_id) REFERENCES public.questions(id)
);
CREATE TABLE public.rematch_requests (
  id integer NOT NULL DEFAULT nextval('rematch_requests_id_seq'::regclass),
  battle_id uuid NOT NULL,
  user_id uuid NOT NULL,
  request_time timestamp with time zone DEFAULT now(),
  is_canceled boolean DEFAULT false,
  CONSTRAINT rematch_requests_pkey PRIMARY KEY (id)
);
CREATE TABLE public.settings (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  key text NOT NULL UNIQUE,
  value text NOT NULL,
  description text,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT settings_pkey PRIMARY KEY (id)
);
CREATE TABLE public.user_status (
  user_id uuid NOT NULL,
  status text NOT NULL DEFAULT 'offline'::text CHECK (status = ANY (ARRAY['online'::text, 'offline'::text, 'away'::text])),
  last_active timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT user_status_pkey PRIMARY KEY (user_id),
  CONSTRAINT user_status_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id)
);