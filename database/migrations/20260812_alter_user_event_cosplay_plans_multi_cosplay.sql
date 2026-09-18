ALTER TABLE `user_event_cosplay_plans`
  DROP INDEX `uq_user_event_cosplay_plans_user_event`,
  ADD UNIQUE KEY `uq_user_event_cosplay_plans_user_event_portfolio` (`user_id`, `event_id`, `portfolio_id`);
